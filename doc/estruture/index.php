<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

/**
 * Leitor:
 *   C:\laragon\www\php_bagisto\doc\extruture\index.php
 *
 * Lido:
 *   C:\laragon\www\php_bagisto
 *
 * Modos:
 *   ?type=1  => linhas "caminho" + filhos com "-> "  (COM NUMERAÇÃO)
 *   ?type=2  => árvore com traços (padrão)          (SEM NUMERAÇÃO)
 *   (vazio)  => árvore com traços (padrão)
 */

// =========================
// CONFIG
// =========================
$TARGET_REL = 'C:\laragon\www\php_bagisto';
$TARGET_DIR = realpath($TARGET_REL);

// ARQUIVOS a ignorar (não mostrar)
$IGNORE_FILES = [
    '.gitignore',
    '.env',
    '.env.example',
];

// PASTAS a ignorar (mostrar caminho, mas não o conteúdo)
$IGNORE_DIRS = [
    '.git',
    '.idea',
    '.vscode',
    'dist',
    'build',
    '.next',
    '.nuxt',
    '.cache',
    'vendor',
    'node_modules',
    'lang',
    'Webkul',
];

$IGNORE_EXTS = [
    // 'log',
];

$ONLY_EXTS = [
    // 'js','jsx','ts','tsx','css','scss','html','json','md','php'
];

// =========================
// TYPE (1 ou 2)
// =========================
$type = isset($_GET['type']) ? (int) $_GET['type'] : 2;
if ($type !== 1 && $type !== 2)
    $type = 2;

// =========================
// DEBUG + VALIDATION
// =========================
echo "LEITOR (index.php): " . __FILE__ . PHP_EOL;
echo "ALVO (relativo)   : " . $TARGET_REL . PHP_EOL;
echo "ALVO (realpath)   : " . ($TARGET_DIR ?: '(realpath falhou)') . PHP_EOL;
echo "TYPE              : " . $type . PHP_EOL . PHP_EOL;

if ($TARGET_DIR === false) {
    echo "ERRO: Não foi possível resolver o caminho: " . $TARGET_REL . PHP_EOL;
    echo "Verifique se o diretório existe e as permissões estão corretas." . PHP_EOL;
    exit(1);
}

if (!is_dir($TARGET_DIR)) {
    echo "ERRO: O caminho não é um diretório válido: " . $TARGET_DIR . PHP_EOL;
    exit(1);
}

// =========================
// HELPERS
// =========================

/**
 * Verifica se um ARQUIVO deve ser completamente ignorado (não mostrar)
 */
function shouldIgnoreFile(string $name, array $ignoreFiles, array $ignoreExts, array $onlyExts): bool
{
    // Ignora arquivos específicos
    if (in_array($name, $ignoreFiles, true))
        return true;

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    // Ignora extensões específicas
    if (!empty($ignoreExts) && $ext !== '' && in_array($ext, $ignoreExts, true))
        return true;

    // Se tem filtro ONLY_EXTS, ignora arquivos que não estão na lista
    if (!empty($onlyExts)) {
        if ($ext === '')
            return true;
        return !in_array($ext, $onlyExts, true);
    }

    return false;
}

/**
 * Verifica se uma PASTA deve ter seu conteúdo ignorado
 */
function shouldIgnoreDirContent(string $name, array $ignoreDirs): bool
{
    return in_array($name, $ignoreDirs, true);
}

function listDirSorted(string $dir): array
{
    $items = scandir($dir);
    if ($items === false)
        return [];

    $dirs = [];
    $files = [];

    foreach ($items as $name) {
        if ($name === '.' || $name === '..')
            continue;
        $full = $dir . DIRECTORY_SEPARATOR . $name;
        if (is_dir($full))
            $dirs[] = $name;
        else
            $files[] = $name;
    }

    natcasesort($dirs);
    natcasesort($files);

    return array_merge(array_values($dirs), array_values($files));
}

function formatFileSuffix(string $name): string
{
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return ($ext !== '') ? " (.$ext)" : " (sem extensão)";
}

/**
 * TYPE=2 (árvore com traços)
 */
function printTreeType2(
    string $dir,
    array $ignoreFiles,
    array $ignoreDirs,
    array $ignoreExts,
    array $onlyExts,
    string $prefix = ''
): void {
    $items = listDirSorted($dir);

    $filtered = [];
    foreach ($items as $name) {
        $full = $dir . DIRECTORY_SEPARATOR . $name;
        $isDir = is_dir($full);

        // Se for arquivo e deve ser ignorado, pula
        if (!$isDir && shouldIgnoreFile($name, $ignoreFiles, $ignoreExts, $onlyExts)) {
            continue;
        }

        $filtered[] = $name;
    }

    $count = count($filtered);

    for ($i = 0; $i < $count; $i++) {
        $name = $filtered[$i];
        $full = $dir . DIRECTORY_SEPARATOR . $name;
        $isDir = is_dir($full);

        $isLast = ($i === $count - 1);
        $branch = $isLast ? "└── " : "├── ";
        $nextPrefix = $prefix . ($isLast ? "    " : "│   ");

        if ($isDir) {
            // Mostra a pasta
            $dirMarker = shouldIgnoreDirContent($name, $ignoreDirs) ? " [ignorado]" : "";
            echo $prefix . $branch . $name . "/" . $dirMarker . PHP_EOL;

            // Se a pasta NÃO está na lista de ignoradas, entra nela
            if (!shouldIgnoreDirContent($name, $ignoreDirs)) {
                printTreeType2($full, $ignoreFiles, $ignoreDirs, $ignoreExts, $onlyExts, $nextPrefix);
            }
        } else {
            echo $prefix . $branch . $name . formatFileSuffix($name) . PHP_EOL;
        }
    }
}

/**
 * TYPE=1 (linhas com caminho; filhos prefixados com "-> ") + NUMERAÇÃO 001, 002, ...
 */
function printType1Numbered(
    string $baseDir,
    array $ignoreFiles,
    array $ignoreDirs,
    array $ignoreExts,
    array $onlyExts
): void {
    $baseDir = rtrim($baseDir, "\\/");
    $rootName = basename($baseDir);
    $counter = 0;

    $emit = function (string $line) use (&$counter) {
        $counter++;
        echo str_pad((string) $counter, 3, '0', STR_PAD_LEFT) . " " . $line . PHP_EOL;
    };

    // itens do root
    $items = listDirSorted($baseDir);

    $rootFiltered = [];
    foreach ($items as $name) {
        $full = $baseDir . DIRECTORY_SEPARATOR . $name;
        $isDir = is_dir($full);

        // Se for arquivo e deve ser ignorado, pula
        if (!$isDir && shouldIgnoreFile($name, $ignoreFiles, $ignoreExts, $onlyExts)) {
            continue;
        }

        $rootFiltered[] = $name;
    }

    foreach ($rootFiltered as $name) {
        $full = $baseDir . DIRECTORY_SEPARATOR . $name;
        $isDir = is_dir($full);

        $rel = $rootName . DIRECTORY_SEPARATOR . $name;
        $relPrint = str_replace('/', '\\', $rel);

        if ($isDir) {
            $dirMarker = shouldIgnoreDirContent($name, $ignoreDirs) ? " [ignorado]" : "";
            $emit($relPrint . $dirMarker);

            // Se a pasta NÃO está na lista de ignoradas, entra nela
            if (!shouldIgnoreDirContent($name, $ignoreDirs)) {
                printType1ChildrenNumbered(
                    $baseDir,
                    $full,
                    $rootName,
                    $ignoreFiles,
                    $ignoreDirs,
                    $ignoreExts,
                    $onlyExts,
                    $emit
                );
            }
        } else {
            $emit($relPrint . formatFileSuffix($name));
        }
    }
}

function printType1ChildrenNumbered(
    string $baseDir,
    string $currentDir,
    string $rootName,
    array $ignoreFiles,
    array $ignoreDirs,
    array $ignoreExts,
    array $onlyExts,
    callable $emit
): void {
    $items = listDirSorted($currentDir);

    $filtered = [];
    foreach ($items as $name) {
        $full = $currentDir . DIRECTORY_SEPARATOR . $name;
        $isDir = is_dir($full);

        // Se for arquivo e deve ser ignorado, pula
        if (!$isDir && shouldIgnoreFile($name, $ignoreFiles, $ignoreExts, $onlyExts)) {
            continue;
        }

        $filtered[] = $name;
    }

    foreach ($filtered as $name) {
        $full = $currentDir . DIRECTORY_SEPARATOR . $name;
        $isDir = is_dir($full);

        $relPath = substr($full, strlen($baseDir) + 1); // remove "baseDir\"
        $rel = $rootName . DIRECTORY_SEPARATOR . $relPath;
        $relPrint = str_replace('/', '\\', $rel);

        if ($isDir) {
            $dirMarker = shouldIgnoreDirContent($name, $ignoreDirs) ? " [ignorado]" : "";
            $emit("-> " . $relPrint . $dirMarker);

            // Se a pasta NÃO está na lista de ignoradas, entra nela
            if (!shouldIgnoreDirContent($name, $ignoreDirs)) {
                printType1ChildrenNumbered(
                    $baseDir,
                    $full,
                    $rootName,
                    $ignoreFiles,
                    $ignoreDirs,
                    $ignoreExts,
                    $onlyExts,
                    $emit
                );
            }
        } else {
            $emit("-> " . $relPrint . formatFileSuffix($name));
        }
    }
}

// =========================
// RUN
// =========================
if ($type === 1) {
    printType1Numbered($TARGET_DIR, $IGNORE_FILES, $IGNORE_DIRS, $IGNORE_EXTS, $ONLY_EXTS);
} else {
    echo basename($TARGET_DIR) . "/" . PHP_EOL;
    printTreeType2($TARGET_DIR, $IGNORE_FILES, $IGNORE_DIRS, $IGNORE_EXTS, $ONLY_EXTS);
}