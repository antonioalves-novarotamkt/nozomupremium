<?php
/* =========================================================
   Nozomu — API de reservas e vouchers
   Guarda tudo no servidor (MySQL ou arquivo) + backup diário.
   ========================================================= */
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('DATA_DIR', __DIR__ . '/data');
define('BK_DIR', __DIR__ . '/backups');
define('EMPTY_DOC', '{"reservations":[],"counter":1000,"vouchers":[]}');

function ensureDirs() {
  foreach ([DATA_DIR, BK_DIR] as $d) {
    if (!is_dir($d)) @mkdir($d, 0775, true);
    $ht = $d . '/.htaccess';
    if (!file_exists($ht)) @file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n");
  }
}
function out($arr, $code = 200) { http_response_code($code); echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function fail($msg, $code = 400) { out(['ok' => false, 'error' => $msg], $code); }
function usingDb() { return defined('DB_HOST') && DB_HOST !== ''; }

function db() {
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    $pdo->exec("CREATE TABLE IF NOT EXISTS nz_store (
      id TINYINT NOT NULL PRIMARY KEY,
      version INT NOT NULL DEFAULT 0,
      data LONGTEXT NOT NULL,
      updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("INSERT IGNORE INTO nz_store (id, version, data, updated_at) VALUES (1, 1, '" . EMPTY_DOC . "', NOW())");
  }
  return $pdo;
}

/* ---------- leitura ---------- */
function readDoc() {
  if (usingDb()) {
    $r = db()->query("SELECT version, data FROM nz_store WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    if (!$r) return ['version' => 1, 'json' => EMPTY_DOC];
    return ['version' => (int)$r['version'], 'json' => $r['data']];
  }
  ensureDirs();
  $f = DATA_DIR . '/store.json';
  if (!file_exists($f)) return ['version' => 1, 'json' => EMPTY_DOC];
  $raw = file_get_contents($f);
  $o = json_decode($raw, true);
  if (!is_array($o) || !isset($o['data'])) return ['version' => 1, 'json' => EMPTY_DOC];
  return ['version' => (int)$o['version'], 'json' => json_encode($o['data'], JSON_UNESCAPED_UNICODE)];
}

/* ---------- escrita com controle de versão ---------- */
function writeDoc($expected, $json) {
  $data = json_decode($json, true);
  if (!is_array($data) || !isset($data['reservations'])) fail('dados inválidos');
  if (usingDb()) {
    $pdo = db();
    $pdo->beginTransaction();
    $cur = $pdo->query("SELECT version, data FROM nz_store WHERE id = 1 FOR UPDATE")->fetch(PDO::FETCH_ASSOC);
    $curV = $cur ? (int)$cur['version'] : 1;
    if ($expected !== null && $expected !== 0 && $expected !== $curV) {
      $pdo->rollBack();
      return ['ok' => false, 'conflict' => true, 'version' => $curV, 'data' => json_decode($cur['data'], true)];
    }
    $newV = $curV + 1;
    $st = $pdo->prepare("UPDATE nz_store SET version = ?, data = ?, updated_at = NOW() WHERE id = 1");
    $st->execute([$newV, $json]);
    $pdo->commit();
    snapshot($json);
    return ['ok' => true, 'version' => $newV];
  }
  ensureDirs();
  $f = DATA_DIR . '/store.json';
  $fp = fopen($f, 'c+');
  if (!$fp) fail('não foi possível gravar (permissão da pasta api/data)', 500);
  flock($fp, LOCK_EX);
  $raw = stream_get_contents($fp);
  $o = json_decode($raw, true);
  $curV = (is_array($o) && isset($o['version'])) ? (int)$o['version'] : 1;
  $curData = (is_array($o) && isset($o['data'])) ? $o['data'] : json_decode(EMPTY_DOC, true);
  if ($expected !== null && $expected !== 0 && $expected !== $curV) {
    flock($fp, LOCK_UN); fclose($fp);
    return ['ok' => false, 'conflict' => true, 'version' => $curV, 'data' => $curData];
  }
  $newV = $curV + 1;
  ftruncate($fp, 0); rewind($fp);
  fwrite($fp, json_encode(['version' => $newV, 'data' => $data], JSON_UNESCAPED_UNICODE));
  fflush($fp); flock($fp, LOCK_UN); fclose($fp);
  snapshot($json);
  return ['ok' => true, 'version' => $newV];
}

/* ---------- backup automático ---------- */
function snapshot($json) {
  ensureDirs();
  @file_put_contents(BK_DIR . '/nz-latest.json', $json);
  $day = BK_DIR . '/nz-' . date('Y-m-d') . '.json';
  @file_put_contents($day, $json);
  $keep = defined('BACKUP_DAYS') ? (int)BACKUP_DAYS : 90;
  $limit = strtotime('-' . $keep . ' days');
  foreach (glob(BK_DIR . '/nz-20*.json') as $f) {
    if (filemtime($f) < $limit) @unlink($f);
  }
}

function body() {
  $raw = file_get_contents('php://input');
  $j = json_decode($raw, true);
  return is_array($j) ? $j : [];
}
function requirePin($j) {
  $pin = isset($j['pin']) ? (string)$j['pin'] : (isset($_GET['pin']) ? (string)$_GET['pin'] : '');
  if (!hash_equals((string)STAFF_PIN, $pin)) fail('senha do painel inválida', 403);
}

/* ---------- rotas ---------- */
$a = isset($_GET['a']) ? $_GET['a'] : 'load';

try {
  if ($a === 'ping') {
    out(['ok' => true, 'modo' => usingDb() ? 'mysql' : 'arquivo', 'php' => PHP_VERSION]);
  }

  if ($a === 'load') {
    $d = readDoc();
    out(['ok' => true, 'version' => $d['version'], 'data' => json_decode($d['json'], true)]);
  }

  /* cliente cria reserva — não precisa de senha */
  if ($a === 'reserve') {
    $j = body();
    $res = isset($j['res']) ? $j['res'] : null;
    if (!$res || !isset($res['nome']) || !isset($res['dia'])) fail('reserva incompleta');
    for ($try = 0; $try < 4; $try++) {
      $d = readDoc();
      $doc = json_decode($d['json'], true);
      foreach ($doc['reservations'] as $r) {
        if (isset($r['id']) && $r['id'] === $res['id']) out(['ok' => true, 'version' => $d['version'], 'dup' => true]);
      }
      $codes = [];
      foreach ($doc['reservations'] as $r) if (isset($r['code'])) $codes[strtoupper($r['code'])] = 1;
      if (isset($codes[strtoupper($res['code'])])) $res['code'] = $res['code'] . substr((string)time(), -2);
      /* voucher: valida e prende à reserva */
      if (!empty($res['voucherCode'])) {
        $vc = strtoupper(trim($res['voucherCode']));
        $achou = false;
        foreach ($doc['vouchers'] as $i => $v) {
          if (strtoupper($v['code']) !== $vc) continue;
          $achou = true;
          $st = isset($v['status']) ? $v['status'] : 'active';
          if ($st === 'used') fail('Este voucher já foi utilizado.', 409);
          if ($st === 'cancelled') fail('Este voucher foi cancelado.', 409);
          if ($st === 'reserved') fail('Este voucher já está em outra reserva.', 409);
          if (!empty($v['expiresAt']) && $v['expiresAt'] < date('Y-m-d')) fail('Este voucher está fora da validade.', 409);
          $doc['vouchers'][$i]['status'] = 'reserved';
          $doc['vouchers'][$i]['reservedBy'] = $res['code'];
          break;
        }
        if (!$achou) fail('Voucher não encontrado.', 409);
      }
      $res['serverAt'] = date('c');
      $doc['reservations'][] = $res;
      $doc['counter'] = (isset($doc['counter']) ? (int)$doc['counter'] : 1000) + 1;
      $w = writeDoc($d['version'], json_encode($doc, JSON_UNESCAPED_UNICODE));
      if (!empty($w['ok'])) out(['ok' => true, 'version' => $w['version'], 'code' => $res['code']]);
      usleep(150000);
    }
    fail('não foi possível salvar a reserva agora, tente novamente', 503);
  }

  /* painel salva o documento inteiro — precisa de senha */
  if ($a === 'save') {
    $j = body();
    requirePin($j);
    if (!isset($j['data'])) fail('sem dados');
    $expected = isset($j['version']) ? (int)$j['version'] : 0;
    $r = writeDoc($expected, json_encode($j['data'], JSON_UNESCAPED_UNICODE));
    out($r);
  }

  /* lista de backups no servidor */
  if ($a === 'backups') {
    requirePin(body());
    ensureDirs();
    $list = [];
    foreach (glob(BK_DIR . '/nz-*.json') as $f) {
      $list[] = ['file' => basename($f), 'size' => filesize($f), 'at' => date('c', filemtime($f))];
    }
    usort($list, function ($x, $y) { return strcmp($y['file'], $x['file']); });
    out(['ok' => true, 'backups' => $list]);
  }

  /* baixar/restaurar um backup */
  if ($a === 'backup') {
    requirePin(body());
    $f = isset($_GET['f']) ? basename($_GET['f']) : '';
    $p = BK_DIR . '/' . $f;
    if (!preg_match('/^nz-[a-z0-9\-]+\.json$/i', $f) || !file_exists($p)) fail('backup não encontrado', 404);
    out(['ok' => true, 'file' => $f, 'data' => json_decode(file_get_contents($p), true)]);
  }

  fail('ação desconhecida: ' . $a, 404);
} catch (Throwable $e) {
  fail('erro no servidor: ' . $e->getMessage(), 500);
}
