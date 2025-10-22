<?php
$root = __DIR__;
$sourceFiles = [
  'القائمه النهائيه للاختبارات والادوات.txt',
  'تقرير_الاختلافات_بين_التقارير.txt',
  'تقرير_الفحص_الشامل_2025.txt',
  'جرد_الاختبارات_والأدوات.txt',
  'قائمة_الاختبارات_والأدوات.txt',
];
$tools = [];
$tests = [];
$debug = [];

function addItem(&$set, $item) {
  $item = trim($item);
  if ($item === '') return;
  $key = mb_strtolower($item);
  $set[$key] = $item;
}

$vendorCandidates = [
  'phpunit','behat','codecept','infection','phpstan','psalm','phpmd','pdepend',
  'php-cs-fixer','phpcs','phpcbf','rector','security-checker','phpdoc','parallel-lint',
  'composer-unused','deptrac','patch-type-declarations','php-parse','carbon',
  'blade-icons-generate','json5'
];

foreach ($sourceFiles as $fn) {
  $path = $root . DIRECTORY_SEPARATOR . $fn;
  $fileTests = 0; $fileTools = 0; $lineCount = 0;
  $sample = 0; $samples = [];
  if (!file_exists($path)) { $debug[] = "Missing: $fn"; continue; }
  $lines = @file($path, FILE_IGNORE_NEW_LINES);
  if (!$lines) { $debug[] = "Empty/Unreadable: $fn"; continue; }
  $currentTestPrefix = '';
  foreach ($lines as $line) {
    $lineCount++;
    $l = trim($line);
    if ($l === '') continue;

    if (strpos($l, 'tests/') !== false && $sample < 5) { $samples[] = $l; $sample++; }

    if (preg_match('/المسار\s*:\s*\.?\/?([A-Za-z0-9_\/-\.]+)/u', $l, $m)) {
      $currentTestPrefix = ltrim($m[1], './');
      if (strpos($currentTestPrefix, 'tests/') !== 0) {
        $currentTestPrefix = ltrim($currentTestPrefix, '/');
      }
    }

    // Explicit test paths
    if (preg_match_all('/tests\/[A-Za-z0-9_\/-\.]+\.php/u', $l, $mm)) {
      foreach ($mm[0] as $p) { addItem($tests, $p); $fileTests++; }
    }

    // Prefixed test names
    if ($currentTestPrefix && preg_match('/([A-Za-z0-9_]+Test\.php)/u', $l, $m2)) {
      $p = rtrim($currentTestPrefix, '/') . '/' . $m2[1];
      $p = preg_replace('/^\.\//', '', $p);
      addItem($tests, $p); $fileTests++;
    }

    // Arabic markers + path
    if (preg_match('/(?:اختبار|أداة اختبار)\s*:\s*([A-Za-z0-9_\/-\.]+\.php)/u', $l, $m3)) {
      $p = preg_replace('/^\.\//', '', $m3[1]);
      addItem($tests, $p); $fileTests++;
    }

    // Fallback: any token ending with Test.php
    if (preg_match_all('/[A-Za-z0-9_\/-\.]*Test\.php/u', $l, $mf)) {
      foreach ($mf[0] as $tok) {
        if (strpos($tok, 'tests/') !== false) { addItem($tests, $tok); $fileTests++; continue; }
        if ($currentTestPrefix) {
          $p = rtrim($currentTestPrefix, '/') . '/' . basename($tok);
          addItem($tests, $p); $fileTests++;
        }
      }
    }

    // Tools
    if (preg_match_all('/(?:^|\s)(\.[A-Za-z0-9_\.-]+|Dockerfile|docker-compose(?:\.[A-Za-z0-9_\.-]+)?|(?:docker|dev-docker|scripts|\.husky|\.github|phpstan|psalm|phpmd|phpunit|phpinsights|platform-tools|public|tmp)\/[A-Za-z0-9_\/-\.]+\.?[A-Za-z0-9_\.-]*)/u', $l, $mt)) {
      foreach ($mt[1] as $p) {
        if ($p === '' || $p === '-') continue;
        addItem($tools, $p); $fileTools++;
      }
    }

    if (preg_match('/\b(' . implode('|', array_map('preg_quote', $vendorCandidates)) . ')\b/i', $l, $mv)) {
      addItem($tools, 'vendor/bin/' . strtolower($mv[1])); $fileTools++;
    }
  }
  $debug[] = sprintf('Processed %s: lines=%d tools=%d tests=%d prefix=%s', $fn, $lineCount, $fileTools, $fileTests, $currentTestPrefix);
  if (!empty($samples)) {
    $debug[] = 'Samples containing tests/: ' . $fn;
    foreach ($samples as $s) { $debug[] = '  ' . $s; }
  }
}

$toolsOut = array_values($tools);
$testsOut = array_values($tests);
sort($toolsOut, SORT_NATURAL | SORT_FLAG_CASE);
sort($testsOut, SORT_NATURAL | SORT_FLAG_CASE);

$out = [];
$out[] = 'COPRRA Tools and Tests (Complete Merged Index)';
$out[] = '';
$out[] = 'Sources:';
foreach ($sourceFiles as $fn) $out[] = '- ' . $fn;
$out[] = '';
$out[] = 'Counts:';
$out[] = '- Tools: ' . count($toolsOut);
$out[] = '- Tests: ' . count($testsOut);
$out[] = '- Total: ' . (count($toolsOut) + count($testsOut));
$out[] = '';
$out[] = 'TOOLS';
$out[] = '-----';
foreach ($toolsOut as $p) $out[] = $p;
$out[] = '';
$out[] = 'TESTS';
$out[] = '-----';
foreach ($testsOut as $p) $out[] = $p;
$out[] = '';
$out[] = 'DEBUG';
$out[] = '-----';
foreach ($debug as $d) $out[] = $d;

$target = $root . DIRECTORY_SEPARATOR . 'اداوات واختبارات كوبر.txt';
file_put_contents($target, implode(PHP_EOL, $out));

echo "Wrote: $target\n";