<?php

// Renders golden files by running the ORIGINAL old-app code (mounted read-only at /old):
//  - /old/libs/services/Pohoda.php and /old/libs/services/Boxxi.php included verbatim,
//  - OrderService::getOrderItemCode/getOrderItemName extracted verbatim from /old/libs/services/OrderService.php
//    (the class itself can not be loaded on PHP 8: it extends Nette\Object, a reserved class name),
//  - Latte 2.3.13 from the old vendor, scratch copy with only `Object` → `LatteObject` renamed (reserved name on PHP >= 7.2).
// Run (no network, old app mounted read-only; the Latte copy is `vendor/latte/latte/src` of the old app with
// `class Object` → `class LatteObject` and `extends (Latte\\)Object` → `extends (Latte\\)LatteObject`):
//   docker run --rm --network none --entrypoint php -v ~/www/ceska-kruta:/old:ro -v <dir>:/scratch ceskakrutacz:latest \
//     -d display_errors=stderr /scratch/harness/render.php /scratch/golden
// with <dir>/harness = this directory (render.php, orders.json, boxxi.json, ../responses/*.xml) and <dir>/old-latte = the patched copy.
// curl_* are replaced by namespaced stubs in App\Services → nothing ever leaves this process. URLs/credentials are never printed.

declare(strict_types=1);

namespace Nette\Database\Table {
    class ActiveRow
    {
        /** @var array<string, mixed> */
        public array $data;
        /** @var array<string, list<ActiveRow>> */
        public array $relatedRows;
        public array $updates = [];

        public function __construct(array $data, array $related = [])
        {
            $this->data = $data;
            $this->relatedRows = $related;
        }

        public function __get($name)
        {
            if (!array_key_exists($name, $this->data)) {
                throw new \LogicException('Unknown column ' . $name);
            }
            return $this->data[$name];
        }

        public function __isset($name)
        {
            return isset($this->data[$name]);
        }

        public function related($table)
        {
            return $this->relatedRows[$table] ?? [];
        }

        public function update($data)
        {
            $this->updates[] = $data;
            return true;
        }
    }
}

namespace Tracy {
    class Debugger
    {
        public static function log($message, $priority = 'info')
        {
        }
    }
}

namespace App\Services {
    final class FakeCurl
    {
        public static array $responses = [];
        public static array $requests = [];
        public static $current = null;
    }

    function curl_init($url)
    {
        FakeCurl::$current = ['body' => null, 'headers' => []];
        return new \stdClass();
    }

    function curl_setopt($ch, $option, $value)
    {
        if ($option === CURLOPT_POSTFIELDS) {
            FakeCurl::$current['body'] = $value;
        }
        if ($option === CURLOPT_HTTPHEADER) {
            // header names only, never values (credentials)
            FakeCurl::$current['headers'] = array_map(static fn ($h) => explode(':', $h, 2)[0], $value);
        }
        return true;
    }

    function curl_exec($ch)
    {
        FakeCurl::$requests[] = FakeCurl::$current;
        return array_shift(FakeCurl::$responses) ?? '';
    }

    function curl_errno($ch)
    {
        return 0;
    }

    function curl_error($ch)
    {
        return '';
    }

    function curl_close($ch)
    {
    }

    // Verbatim methods of the old OrderService
    $source = file_get_contents('/old/libs/services/OrderService.php');
    $start = strpos($source, '    public function getOrderItemName(');
    $end = strrpos($source, '}');
    $methods = substr($source, $start, $end - $start);
    eval('namespace App\Services; use Nette\Database\Table\ActiveRow; final class OrderService {' . $methods . '}');
}

namespace {
    require '/scratch/old-latte/src/latte.php';
    require '/old/libs/services/Pohoda.php';
    require '/old/libs/services/Boxxi.php';

    use App\Services\FakeCurl;
    use Nette\Database\Table\ActiveRow;

    $out = $argv[1];
    @mkdir($out . '/pohoda', 0777, true);
    @mkdir($out . '/boxxi', 0777, true);

    $places = [
        1 => ['id' => 1, 'name' => 'Rychvald - Prodejna', 'is_cool_balik' => null, 'force_delivery_address' => 0],
        7 => ['id' => 7, 'name' => 'Rozvoz domů Přerov', 'is_cool_balik' => null, 'force_delivery_address' => 1],
        21 => ['id' => 21, 'name' => 'Rozvoz CoolBalík', 'is_cool_balik' => 1, 'force_delivery_address' => 1],
    ];
    $products = [
        1 => ['name' => 'Krůta celá s droby', 'code' => null, 'unit' => 'ks', 'weight_per_unit' => 1.0],
        2 => ['name' => 'Půlka krůty bez drobů', 'code' => null, 'unit' => 'ks', 'weight_per_unit' => 1.0],
        41 => ['name' => 'Porcovaná krůta - celá s droby', 'code' => null, 'unit' => 'ks', 'weight_per_unit' => 1.0],
        100 => ['name' => 'Krůtí prsa', 'code' => '100100', 'unit' => 'kg', 'weight_per_unit' => 1.0],
        101 => ['name' => 'Krůtí šunka', 'code' => '200100', 'unit' => 'ks', 'weight_per_unit' => 0.3],
    ];

    // Same data as tests/database/fixtures/50-orders.sql (values typed like Nette Database normalizes them)
    $orders = json_decode(file_get_contents('/scratch/harness/orders.json'), true);

    $makeRow = static function (array $o) use ($places, $products): ActiveRow {
        $items = [];
        foreach ($o['items'] as $i) {
            $items[] = new ActiveRow($i + ['product' => new ActiveRow($products[$i['product_id']])]);
        }
        $data = $o;
        unset($data['items']);
        $data['date'] = new \DateTime($o['date']);
        $data['ins_dt'] = new \DateTime($o['ins_dt']);
        $data['place'] = new ActiveRow($places[$o['place_id']]);
        return new ActiveRow($data, ['order_item' => $items]);
    };

    $responses = [
        'found' => file_get_contents('/scratch/harness/search-found.xml'),
        'not_found' => file_get_contents('/scratch/harness/search-not-found.xml'),
        'created' => file_get_contents('/scratch/harness/address-created.xml'),
        'order' => file_get_contents('/scratch/harness/order-ok.xml'),
    ];

    $pohoda = new App\Services\Pohoda((new \ReflectionClass(App\Services\OrderService::class))->newInstance());

    foreach ($orders as $o) {
        if (!$o['golden']) {
            continue;
        }
        $row = $makeRow($o);
        FakeCurl::$requests = [];
        FakeCurl::$responses = $row->place->force_delivery_address
            ? [$responses['found'], $responses['order'], $responses['order']]
            : [$responses['order'], $responses['order']];

        $pohoda->sendOrderRequest($row);

        $requests = FakeCurl::$requests;
        if ($row->place->force_delivery_address) {
            $search = array_shift($requests);
            file_put_contents(sprintf('%s/pohoda/search-address-%d.xml', $out, $o['id']), $search['body']);
        }
        foreach ($requests as $r) {
            preg_match('~<ord:numberOrder>(.*)</ord:numberOrder>~', $r['body'], $m);
            file_put_contents(sprintf('%s/pohoda/order-%s.xml', $out, $m[1]), $r['body']);
        }
        fwrite(STDERR, sprintf("order %d: %d requests, headers %s\n", $o['id'], count(FakeCurl::$requests), implode(',', FakeCurl::$requests[0]['headers'])));
    }

    // Address create (when search returns nothing)
    $row = $makeRow($orders['5902']);
    FakeCurl::$requests = [];
    FakeCurl::$responses = [$responses['not_found'], $responses['created'], $responses['order']];
    $pohoda->sendOrderRequest($row);
    file_put_contents($out . '/pohoda/create-address-5902.xml', FakeCurl::$requests[1]['body']);
    file_put_contents($out . '/pohoda/order-WEB5902-address-7777.xml', FakeCurl::$requests[2]['body']);

    // Boxxi: the row after the form update is re-fetched by Nette (date/price/box count normalized from DB)
    foreach (json_decode(file_get_contents('/scratch/harness/boxxi.json'), true) as $case) {
        $o = $orders[(string) $case['order_id']];
        $o['date'] = $case['date'];
        $o['cool_balik_price'] = (float) $case['cool_balik_price'];
        $o['cool_balik_box_count'] = (int) $case['cool_balik_box_count'];
        $row = $makeRow($o);
        FakeCurl::$requests = [];
        FakeCurl::$responses = ['{"Id":1}'];
        (new App\Services\Boxxi())->sendOrderRequest($row, (float) $case['weight']);
        file_put_contents(sprintf('%s/boxxi/%s.json', $out, $case['name']), FakeCurl::$requests[0]['body']);
        fwrite(STDERR, sprintf("boxxi %s: headers %s\n", $case['name'], implode(',', FakeCurl::$requests[0]['headers'])));
    }

}
