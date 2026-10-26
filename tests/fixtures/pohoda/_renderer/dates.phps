<?php
// Prints the old Pohoda::calculateDateTo / calculateDateFrom results (private methods called via reflection, code verbatim)
declare(strict_types=1);
namespace Nette\Database\Table { class ActiveRow { public function __construct(public array $data) {} public function __get($n) { return $this->data[$n]; } } }
namespace Tracy { class Debugger { public static function log($m, $p = null) {} } }
namespace App\Services { final class OrderService {} }
namespace {
    require '/old/libs/services/Pohoda.php';
    use Nette\Database\Table\ActiveRow;
    $p = new App\Services\Pohoda(new App\Services\OrderService());
    $to = new ReflectionMethod($p, 'calculateDateTo'); $to->setAccessible(true);
    $from = new ReflectionMethod($p, 'calculateDateFrom'); $from->setAccessible(true);
    $places = ['shop' => [30, null, 0], 'shop1' => [1, null, 0], 'delivery' => [7, null, 1], 'coolbalik' => [21, 1, 1]];
    foreach ($places as $k => [$id, $cb, $fd]) {
        $place = new ActiveRow(['is_cool_balik' => $cb, 'force_delivery_address' => $fd]);
        for ($d = 17; $d <= 23; $d++) {
            $date = new DateTime("2024-06-$d");
            $order = new ActiveRow(['date' => $date, 'place' => $place, 'place_id' => $id]);
            echo "to $k 2024-06-$d ", $to->invoke($p, $order)->format('Y-m-d'), "\n";
            foreach ([false, true] as $t) {
                echo "from $k 2024-06-$d ", $t ? 'turkey' : 'other', ' ', $from->invoke($p, $order, new DateTime("2024-06-$d"), $t)->format('Y-m-d'), "\n";
            }
        }
    }
}
