<?php
declare(strict_types=1);

function rupiah(float $v): string {
    return 'Rp ' . number_format($v, 0, ',', '.');
}

function indo_date_long(?string $date = null): string {
    $days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $months = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $ts = $date ? strtotime($date) : time();
    $d = (int)date('w', $ts);
    $day = $days[$d];
    $m = (int)date('n', $ts);
    $month = $months[$m];
    return $day . ', ' . date('j', $ts) . ' ' . $month . ' ' . date('Y', $ts);
}
