<?php
declare(strict_types=1);
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $url): never { header('Location: '.$url); exit; }
function json_response(array $data, int $status=200): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function is_post(): bool { return $_SERVER['REQUEST_METHOD']==='POST'; }
function post(string $key): string { return trim($_POST[$key]??''); }
function get_param(string $key): string { return trim($_GET[$key]??''); }
function fmt_date(?string $date, string $format='M d, Y'): string { if (!$date) return '—'; return date($format, strtotime($date)); }
function fmt_money(float|string $amount): string { return '₱ '.number_format((float)$amount, 2); }
function month_start(): string { return date('Y-m-01'); }
function today_date(): string { return date('Y-m-d'); }
function date_range(): array {
    $from = get_param('from'); $to = get_param('to');
    $from = ($from && strtotime($from)) ? $from : month_start();
    $to   = ($to   && strtotime($to))   ? $to   : today_date();
    if ($from > $to) [$from,$to] = [$to,$from];
    return compact('from','to');
}
function stream_csv(array $rows, string $filename): never {
    if (empty($rows)) { http_response_code(204); exit; }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'.csv"');
    $out = fopen('php://output','w'); fputs($out,"\xEF\xBB\xBF");
    fputcsv($out, array_keys($rows[0]));
    foreach ($rows as $row) fputcsv($out, $row);
    fclose($out); exit;
}
function get_settings(): array {
    static $cache = null;
    if ($cache === null) { $rows = get_db()->query('SELECT key_name,value FROM settings')->fetchAll(); $cache = array_column($rows,'value','key_name'); }
    return $cache;
}
function setting(string $key, string $default=''): string { return get_settings()[$key]??$default; }
