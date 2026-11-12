<?php
/**
 * CKC — titres anglais du Mega Menu (ets_megamenu) pour la boutique espace pro.
 *
 * La boutique particulier a déjà les libellés EN. L’espace pro a les mêmes entrées
 * (menus dupliqués) mais le texte EN est resté en français. Les URLs EN
 * (link, url, title_link, href du HTML) ne sont pas modifiées.
 *
 * Dry-run (défaut, aucune écriture) :
 *   php scripts/translate_pro_megamenu_en.php
 *
 * Écriture :
 *   php scripts/translate_pro_megamenu_en.php --apply
 *
 * Options :
 *   --source-shop=1   boutique dont on copie les traductions (particulier)
 *   --target-shop=2   boutique à corriger (espace pro)
 *   --from-iso=fr
 *   --iso=en
 *
 * Libellés FR propres à l’espace pro (absents du menu particulier) :
 *   Occultation → Privacy screen
 *   Lames en PVC → PVC blades
 *
 * Avec --apply : cache module ets_megamenu vidé, et cache pagecache si le module est actif.
 * Un JSONL de rollback est écrit dans var/logs/.
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI uniquement.\n");
    exit(1);
}

if (in_array('--self-test', $argv ?? [], true)) {
    exit(proMegaMenuSelfTest());
}

require_once dirname(__DIR__) . '/config/config.inc.php';

$apply = in_array('--apply', $argv, true);
$sourceShop = 1;
$targetShop = 2;
$isoFrom = 'fr';
$isoTo = 'en';

foreach ($argv as $arg) {
    if (preg_match('/^--source-shop=(\d+)$/', $arg, $m)) {
        $sourceShop = (int) $m[1];
    } elseif (preg_match('/^--target-shop=(\d+)$/', $arg, $m)) {
        $targetShop = (int) $m[1];
    } elseif (preg_match('/^--from-iso=([a-z]{2})$/i', $arg, $m)) {
        $isoFrom = strtolower($m[1]);
    } elseif (preg_match('/^--iso=([a-z]{2})$/i', $arg, $m)) {
        $isoTo = strtolower($m[1]);
    }
}

$fromId = (int) Language::getIdByIso($isoFrom);
$toId = (int) Language::getIdByIso($isoTo);
if ($fromId <= 0 || $toId <= 0 || $fromId === $toId) {
    fwrite(STDERR, "Langues {$isoFrom}/{$isoTo} introuvables.\n");
    exit(1);
}
if ($sourceShop <= 0 || $targetShop <= 0 || $sourceShop === $targetShop) {
    fwrite(STDERR, "Boutiques source et cible invalides.\n");
    exit(1);
}

$translator = new ProMegaMenuEnTranslator($sourceShop, $targetShop, $fromId, $toId, $apply);
exit($translator->run());

/**
 * Libellés FR qui n’existent pas sur la boutique particulier.
 *
 * @return array<string,string>
 */
function proMegaMenuOverrides(): array
{
    return [
        'Occultation' => 'Privacy screen',
        'Lames en PVC' => 'PVC blades',
    ];
}

function proMegaMenuNorm(string $value): string
{
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', trim($value));

    return $value ?? '';
}

/**
 * @return array<int,array{text:string,offset:int,length:int,raw:string}>
 */
function proMegaMenuTextNodes(string $html): array
{
    $nodes = [];
    if ($html === '' || !preg_match_all('/>([^<]*)</u', $html, $matches, PREG_OFFSET_CAPTURE)) {
        return $nodes;
    }
    foreach ($matches[1] as $hit) {
        $raw = $hit[0];
        $text = proMegaMenuNorm($raw);
        if ($text === '') {
            continue;
        }
        $nodes[] = [
            'text' => $text,
            'offset' => (int) $hit[1],
            'length' => strlen($raw),
            'raw' => $raw,
        ];
    }

    return $nodes;
}

/**
 * @return array<int,array{text:string,offset:int,length:int,quote:string}>
 */
function proMegaMenuAltNodes(string $html): array
{
    $nodes = [];
    if ($html === '' || !preg_match_all('/\balt\s*=\s*(["\'])(.*?)\1/su', $html, $matches, PREG_OFFSET_CAPTURE)) {
        return $nodes;
    }
    foreach ($matches[2] as $i => $hit) {
        $text = proMegaMenuNorm($hit[0]);
        if ($text === '') {
            continue;
        }
        $nodes[] = [
            'text' => $text,
            'offset' => (int) $hit[1],
            'length' => strlen($hit[0]),
            'quote' => $matches[1][$i][0],
        ];
    }

    return $nodes;
}

/**
 * @param array<int,string> $replacements index => nouveau texte
 */
function proMegaMenuApplyNodes(string $html, array $replacements, string $kind): string
{
    if (!$replacements) {
        return $html;
    }
    $nodes = $kind === 'alt' ? proMegaMenuAltNodes($html) : proMegaMenuTextNodes($html);
    krsort($replacements);
    foreach ($replacements as $index => $newText) {
        if (!isset($nodes[$index])) {
            continue;
        }
        $node = $nodes[$index];
        if ($kind === 'alt') {
            $chunk = $newText;
        } else {
            if (!preg_match('/^(\s*)(.*?)(\s*)$/su', $node['raw'], $parts)) {
                $chunk = $newText;
            } else {
                $chunk = $parts[1] . $newText . $parts[3];
            }
        }
        $html = substr_replace($html, $chunk, $node['offset'], $node['length']);
    }

    return $html;
}

/**
 * @return int[]
 */
function proMegaMenuHrefIds(string $html): array
{
    if ($html === '' || !preg_match_all('/\bhref\s*=\s*["\'][^"\']*?\/(?:[a-z]{2}\/)?(\d+)-/i', $html, $matches)) {
        return [];
    }

    return array_map('intval', $matches[1]);
}

function proMegaMenuSignature(string $title, string $titleLink, string $content): string
{
    $ids = array_merge(proMegaMenuHrefIds($titleLink), proMegaMenuHrefIds($content));

    return proMegaMenuNorm($title) . '|' . implode(',', $ids);
}

function proMegaMenuSelfTest(): int
{
    $html = "<p>\n    Clôture\n</p><a href=\"/en/94-rigid-fence\">Kit Grillage rigide à sceller<img alt=\"Kit Grillage rigide à sceller\"></a><span>Occultation</span>";
    $texts = proMegaMenuTextNodes($html);
    $alts = proMegaMenuAltNodes($html);
    $fail = 0;
    $expectTexts = ['Clôture', 'Kit Grillage rigide à sceller', 'Occultation'];
    foreach ($expectTexts as $i => $expected) {
        if (($texts[$i]['text'] ?? '') !== $expected) {
            fwrite(STDERR, "text node {$i} attendu {$expected}\n");
            $fail++;
        }
    }
    if (($alts[0]['text'] ?? '') !== 'Kit Grillage rigide à sceller') {
        fwrite(STDERR, "alt non lu\n");
        $fail++;
    }
    $out = proMegaMenuApplyNodes($html, [0 => 'Fence', 1 => 'In-ground Rigid Mesh Fencing Kit'], 'text');
    $out = proMegaMenuApplyNodes($out, [0 => 'In-ground Rigid Mesh Fencing Kit'], 'alt');
    if (strpos($out, 'Fence') === false || strpos($out, 'Occultation') === false || strpos($out, '/en/94-rigid-fence') === false) {
        fwrite(STDERR, "remplacement texte incorrect\n{$out}\n");
        $fail++;
    }
    if (strpos($out, 'alt="In-ground Rigid Mesh Fencing Kit"') === false) {
        fwrite(STDERR, "remplacement alt incorrect\n{$out}\n");
        $fail++;
    }
    if (proMegaMenuSignature('Kits prêts à poser', '/93-kits', '<a href="/94-kit">x</a>')
        !== proMegaMenuSignature('Kits prêts à poser', '/en/93-rigid', '<a href="/en/94-rigid-fence">y</a>')) {
        fwrite(STDERR, "signature href instable\n");
        $fail++;
    }
    if ($fail > 0) {
        fwrite(STDERR, "self-test: {$fail} échec(s)\n");
        return 1;
    }
    echo "self-test ok\n";

    return 0;
}

final class ProMegaMenuEnTranslator
{
    /** @var array<string,array<string,true>> */
    private array $dict = [
        'menu' => [],
        'tab' => [],
        'block' => [],
        'bubble' => [],
        'content' => [],
    ];

    /** @var array<string,array<string,true>> */
    private array $ambiguous = [];

    /** @var array<string,true> */
    private array $knownFr = [];

    /** @var array<int,array<string,mixed>> */
    private array $sourceBlocks = [];

    /** @var array<string,array<string,mixed>> */
    private array $sourceBlocksBySig = [];

    /** @var string[] */
    private array $missing = [];

    /** @var array<int,array{table:string,id:int,field:string,from:string,to:string}> */
    private array $changes = [];

    /** @var array<int,array{table:string,id:int,field:string,old:string}> */
    private array $rollback = [];

    /** @var int */
    private $sourceShop;

    /** @var int */
    private $targetShop;

    /** @var int */
    private $fromLang;

    /** @var int */
    private $toLang;

    /** @var bool */
    private $apply;

    /**
     * @param int  $sourceShop
     * @param int  $targetShop
     * @param int  $fromLang
     * @param int  $toLang
     * @param bool $apply
     */
    public function __construct($sourceShop, $targetShop, $fromLang, $toLang, $apply)
    {
        $this->sourceShop = (int) $sourceShop;
        $this->targetShop = (int) $targetShop;
        $this->fromLang = (int) $fromLang;
        $this->toLang = (int) $toLang;
        $this->apply = (bool) $apply;
    }

    public function run(): int
    {
        foreach (['ets_mm_menu_lang', 'ets_mm_tab_lang', 'ets_mm_block_lang', 'ets_mm_menu', 'ets_mm_column', 'ets_mm_block'] as $table) {
            if (!$this->tableExists($table)) {
                fwrite(STDERR, "Table {$table} absente.\n");
                return 1;
            }
        }

        $sourceMenus = $this->menuIdsForShop($this->sourceShop);
        $targetMenus = $this->menuIdsForShop($this->targetShop);
        $shared = array_values(array_intersect($sourceMenus, $targetMenus));
        $targetOnly = array_values(array_diff($targetMenus, $sourceMenus));

        echo 'Boutique source ' . $this->sourceShop . ' : ' . count($sourceMenus) . " menu(s)\n";
        echo 'Boutique cible ' . $this->targetShop . ' : ' . count($targetMenus) . " menu(s)\n";
        echo 'Menus partagés (ignorés) : ' . count($shared) . "\n";
        echo 'Menus cible seuls : ' . count($targetOnly) . "\n";
        if (!$targetOnly) {
            fwrite(STDERR, "Aucun menu propre à la boutique cible. Rien à faire.\n");
            return 1;
        }

        $db = Db::getInstance();
        if ($this->apply) {
            $db->execute('START TRANSACTION');
        }
        try {
            $this->learnSource($sourceMenus);
            $this->translateMenus($targetOnly);
            $this->translateTabs($this->idsForMenus('ets_mm_tab', 'id_tab', $targetOnly));
            $this->translateBlocks($this->blockIdsForMenus($targetOnly));
            if ($this->apply) {
                $db->execute('COMMIT');
            }
        } catch (Throwable $e) {
            if ($this->apply) {
                $db->execute('ROLLBACK');
            }
            throw $e;
        }

        $this->printReport();
        if (!$this->apply) {
            echo "\nDry-run. Relancer avec --apply pour écrire.\n";
            return 0;
        }
        if (!$this->changes) {
            echo "\nAucune écriture.\n";
            return 0;
        }

        $this->writeRollback();
        $this->clearMenuCache();
        $this->clearPageCache();
        echo "\nÉcriture terminée (" . count($this->changes) . " champ(s)).\n";

        return 0;
    }

    /**
     * @param int[] $menuIds
     */
    private function learnSource(array $menuIds): void
    {
        $menuIds = array_values(array_diff($menuIds, $this->menuIdsForShop($this->targetShop)));
        foreach ($this->langRows('ets_mm_menu_lang', 'id_menu', $menuIds) as $id => $pair) {
            $this->learnScalar('menu', (string) ($pair['fr']['title'] ?? ''), (string) ($pair['en']['title'] ?? ''));
            $this->learnScalar('bubble', (string) ($pair['fr']['bubble_text'] ?? ''), (string) ($pair['en']['bubble_text'] ?? ''));
        }
        $tabIds = $this->idsForMenus('ets_mm_tab', 'id_tab', $menuIds);
        foreach ($this->langRows('ets_mm_tab_lang', 'id_tab', $tabIds) as $pair) {
            $this->learnScalar('tab', (string) ($pair['fr']['title'] ?? ''), (string) ($pair['en']['title'] ?? ''));
            $this->learnScalar('bubble', (string) ($pair['fr']['bubble_text'] ?? ''), (string) ($pair['en']['bubble_text'] ?? ''));
        }
        $blockIds = $this->blockIdsForMenus($menuIds);
        $rows = $this->langRows('ets_mm_block_lang', 'id_block', $blockIds);
        foreach ($rows as $id => $pair) {
            $fr = $pair['fr'];
            $en = $pair['en'];
            $this->learnScalar('block', (string) ($fr['title'] ?? ''), (string) ($en['title'] ?? ''));
            $this->learnNodes((string) ($fr['content'] ?? ''), (string) ($en['content'] ?? ''));
            $sig = proMegaMenuSignature(
                (string) ($fr['title'] ?? ''),
                (string) ($fr['title_link'] ?? ''),
                (string) ($fr['content'] ?? '')
            );
            $block = [
                'id' => $id,
                'title' => proMegaMenuNorm((string) ($en['title'] ?? '')),
                'fr_title' => proMegaMenuNorm((string) ($fr['title'] ?? '')),
                'texts' => array_column(proMegaMenuTextNodes((string) ($en['content'] ?? '')), 'text'),
                'fr_texts' => array_column(proMegaMenuTextNodes((string) ($fr['content'] ?? '')), 'text'),
                'alts' => array_column(proMegaMenuAltNodes((string) ($en['content'] ?? '')), 'text'),
                'fr_alts' => array_column(proMegaMenuAltNodes((string) ($fr['content'] ?? '')), 'text'),
            ];
            $this->sourceBlocks[$id] = $block;
            $this->sourceBlocksBySig[$sig][] = $block;
        }
    }

    /**
     * @param int[] $menuIds
     */
    private function translateMenus(array $menuIds): void
    {
        foreach ($this->langRows('ets_mm_menu_lang', 'id_menu', $menuIds) as $id => $pair) {
            $this->translateScalar('ets_mm_menu_lang', 'id_menu', $id, 'title', 'menu', $pair);
            $this->translateScalar('ets_mm_menu_lang', 'id_menu', $id, 'bubble_text', 'bubble', $pair);
        }
    }

    /**
     * @param int[] $tabIds
     */
    private function translateTabs(array $tabIds): void
    {
        foreach ($this->langRows('ets_mm_tab_lang', 'id_tab', $tabIds) as $id => $pair) {
            $this->translateScalar('ets_mm_tab_lang', 'id_tab', $id, 'title', 'tab', $pair);
            $this->translateScalar('ets_mm_tab_lang', 'id_tab', $id, 'bubble_text', 'bubble', $pair);
        }
    }

    /**
     * @param int[] $blockIds
     */
    private function translateBlocks(array $blockIds): void
    {
        foreach ($this->langRows('ets_mm_block_lang', 'id_block', $blockIds) as $id => $pair) {
            $fr = $pair['fr'];
            $en = $pair['en'];
            $sig = proMegaMenuSignature(
                (string) ($fr['title'] ?? ''),
                (string) ($fr['title_link'] ?? ''),
                (string) ($fr['content'] ?? '')
            );
            $match = $this->sourceBlocksBySig[$sig] ?? [];
            $matched = count($match) === 1 ? $match[0] : null;

            $titleFrom = proMegaMenuNorm((string) ($en['title'] ?? ''));
            $titleFr = proMegaMenuNorm((string) ($fr['title'] ?? ''));
            $titleTo = null;
            if ($titleFr !== '' && ($titleFrom === '' || $titleFrom === $titleFr)) {
                if ($matched && $matched['title'] !== '' && $matched['title'] !== $titleFr) {
                    $titleTo = $matched['title'];
                } else {
                    $titleTo = $this->resolve('block', $titleFr);
                }
            }
            if ($titleTo !== null && $titleTo !== $titleFrom) {
                $this->queue('ets_mm_block_lang', 'id_block', $id, 'title', (string) ($en['title'] ?? ''), $titleTo);
            } elseif ($titleFr !== '' && ($titleFrom === '' || $titleFrom === $titleFr) && $titleTo === null) {
                $this->markMissing($titleFr);
            }

            $this->translateContentField($id, (string) ($fr['content'] ?? ''), (string) ($en['content'] ?? ''), $matched);
        }
    }

    /**
     * @param array<string,mixed>|null $matched
     */
    private function translateContentField(int $id, string $frHtml, string $enHtml, ?array $matched): void
    {
        $frTexts = array_column(proMegaMenuTextNodes($frHtml), 'text');
        $enTexts = array_column(proMegaMenuTextNodes($enHtml), 'text');
        $frAlts = array_column(proMegaMenuAltNodes($frHtml), 'text');
        $enAlts = array_column(proMegaMenuAltNodes($enHtml), 'text');

        $textMap = $this->nodeMap($frTexts, $enTexts, $matched, 'texts', 'fr_texts', 'content');
        $altMap = $this->nodeMap($frAlts, $enAlts, $matched, 'alts', 'fr_alts', 'content');
        if (!$textMap && !$altMap) {
            return;
        }

        $updated = proMegaMenuApplyNodes($enHtml, $textMap, 'text');
        $updated = proMegaMenuApplyNodes($updated, $altMap, 'alt');
        if ($updated === $enHtml) {
            return;
        }
        $this->queue('ets_mm_block_lang', 'id_block', $id, 'content', $enHtml, $updated, count($textMap) + count($altMap));
    }

    /**
     * @param string[] $frNodes
     * @param string[] $enNodes
     * @param array<string,mixed>|null $matched
     *
     * @return array<int,string>
     */
    private function nodeMap(array $frNodes, array $enNodes, ?array $matched, string $enKey, string $frKey, string $bucket): array
    {
        $map = [];
        $positional = $matched
            && count($frNodes) === count($enNodes)
            && count($frNodes) === count($matched[$frKey])
            && count($frNodes) === count($matched[$enKey]);

        foreach ($enNodes as $i => $current) {
            $source = $frNodes[$i] ?? $current;
            if ($current !== '' && $current !== $source) {
                continue;
            }
            $replacement = null;
            $sameSourceNode = $positional && ($matched[$frKey][$i] ?? '') === $source;
            if ($sameSourceNode && ($matched[$enKey][$i] ?? '') !== '' && $matched[$enKey][$i] !== $current) {
                $replacement = $matched[$enKey][$i];
            } else {
                $replacement = $this->resolve($bucket, $source);
            }
            if ($replacement === null) {
                if ($source !== '') {
                    $this->markMissing($source);
                }
                continue;
            }
            if ($replacement !== $current) {
                $map[$i] = $replacement;
            }
        }

        return $map;
    }

    /**
     * @param array{fr:array<string,string>,en:array<string,string>} $pair
     */
    private function translateScalar(string $table, string $idCol, int $id, string $field, string $bucket, array $pair): void
    {
        if (!isset($pair['fr'][$field])) {
            return;
        }
        $fr = proMegaMenuNorm((string) $pair['fr'][$field]);
        $enRaw = (string) ($pair['en'][$field] ?? '');
        $en = proMegaMenuNorm($enRaw);
        if ($fr === '' || ($en !== '' && $en !== $fr)) {
            return;
        }
        $to = $this->resolve($bucket, $fr);
        if ($to === null) {
            $this->markMissing($fr);
            return;
        }
        if ($to === $en) {
            return;
        }
        $this->queue($table, $idCol, $id, $field, $enRaw, $to);
    }

    private function resolve(string $bucket, string $fr): ?string
    {
        if (isset(proMegaMenuOverrides()[$fr])) {
            return proMegaMenuOverrides()[$fr];
        }
        if (!empty($this->ambiguous[$bucket][$fr])) {
            return null;
        }
        if (isset($this->dict[$bucket][$fr]) && count($this->dict[$bucket][$fr]) === 1) {
            $value = (string) array_key_first($this->dict[$bucket][$fr]);
        } elseif (
            $bucket !== 'content'
            && empty($this->ambiguous['content'][$fr])
            && isset($this->dict['content'][$fr])
            && count($this->dict['content'][$fr]) === 1
        ) {
            $value = (string) array_key_first($this->dict['content'][$fr]);
        } else {
            return null;
        }
        if ($value === '' || $value === $fr) {
            return null;
        }

        return $value;
    }

    private function learnScalar(string $bucket, string $fr, string $en): void
    {
        $fr = proMegaMenuNorm($fr);
        $en = proMegaMenuNorm($en);
        if ($fr === '' || $en === '') {
            return;
        }
        $this->knownFr[$fr] = true;
        if ($en === $fr) {
            return;
        }
        $this->dict[$bucket][$fr][$en] = true;
        if (count($this->dict[$bucket][$fr]) > 1) {
            $this->ambiguous[$bucket][$fr] = true;
        }
    }

    private function learnNodes(string $frHtml, string $enHtml): void
    {
        foreach ([['text', proMegaMenuTextNodes($frHtml), proMegaMenuTextNodes($enHtml)], ['alt', proMegaMenuAltNodes($frHtml), proMegaMenuAltNodes($enHtml)]] as $set) {
            [, $frNodes, $enNodes] = $set;
            if (count($frNodes) !== count($enNodes)) {
                continue;
            }
            foreach ($frNodes as $i => $frNode) {
                $this->learnScalar('content', $frNode['text'], $enNodes[$i]['text']);
            }
        }
    }

    private function markMissing(string $fr): void
    {
        if ($fr === '' || isset($this->knownFr[$fr]) || isset(proMegaMenuOverrides()[$fr])) {
            return;
        }
        $this->missing[$fr] = true;
    }

    private function queue(string $table, string $idCol, int $id, string $field, string $old, string $new, int $count = 1): void
    {
        $where = '`' . bqSQL($idCol) . '` = ' . (int) $id . ' AND id_lang = ' . (int) $this->toLang;
        if ($this->apply) {
            $ok = Db::getInstance()->update($table, [$field => $new], $where, 0, true);
            if (!$ok) {
                throw new RuntimeException('Update impossible ' . $table . ' ' . $id . ' ' . $field);
            }
        }
        $this->rollback[] = ['table' => $table, 'id' => $id, 'field' => $field, 'old' => $old];
        $preview = $field === 'content' ? ($count . ' libellé(s) dans le HTML') : $new;
        $fromPreview = $field === 'content' ? 'HTML' : proMegaMenuNorm($old);
        $this->changes[] = [
            'table' => $table,
            'id' => $id,
            'field' => $field,
            'from' => $fromPreview,
            'to' => $preview,
        ];
    }

    /**
     * @param int[] $ids
     *
     * @return array<int,array{fr:array<string,string>,en:array<string,string>}>
     */
    private function langRows(string $table, string $idCol, array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . bqSQL($table) . '`
            WHERE `' . bqSQL($idCol) . '` IN (' . implode(',', $ids) . ')
              AND id_lang IN (' . (int) $this->fromLang . ',' . (int) $this->toLang . ')'
        );
        $out = [];
        foreach ($rows ?: [] as $row) {
            $id = (int) $row[$idCol];
            $slot = ((int) $row['id_lang'] === $this->toLang) ? 'en' : 'fr';
            $out[$id][$slot] = $row;
        }
        foreach ($out as $id => $pair) {
            if (empty($pair['fr'])) {
                unset($out[$id]);
                continue;
            }
            if (empty($pair['en'])) {
                $out[$id]['en'] = $this->copyLangRow($table, $idCol, $id, $pair['fr']);
            }
        }

        return $out;
    }

    /**
     * @param array<string,string> $frRow
     *
     * @return array<string,string>
     */
    private function copyLangRow(string $table, string $idCol, int $id, array $frRow): array
    {
        $copy = $frRow;
        $copy['id_lang'] = (string) $this->toLang;
        if ($this->apply) {
            $data = $copy;
            unset($data[$idCol]);
            $data['id_lang'] = $this->toLang;
            $data[$idCol] = $id;
            Db::getInstance()->insert($table, $data, false, true, Db::INSERT_IGNORE);
        }

        return $copy;
    }

    /**
     * @return int[]
     */
    private function menuIdsForShop(int $shopId): array
    {
        if ($this->tableExists('ets_mm_menu_shop')) {
            $rows = Db::getInstance()->executeS(
                'SELECT id_menu FROM `' . _DB_PREFIX_ . 'ets_mm_menu_shop` WHERE id_shop = ' . (int) $shopId
            );

            return array_map('intval', array_column($rows ?: [], 'id_menu'));
        }
        if ($this->columnExists('ets_mm_menu', 'id_shop')) {
            $rows = Db::getInstance()->executeS(
                'SELECT id_menu FROM `' . _DB_PREFIX_ . 'ets_mm_menu` WHERE id_shop = ' . (int) $shopId
            );

            return array_map('intval', array_column($rows ?: [], 'id_menu'));
        }
        throw new RuntimeException('Association menu / boutique introuvable (ets_mm_menu_shop).');
    }

    /**
     * @param int[] $menuIds
     *
     * @return int[]
     */
    private function idsForMenus(string $table, string $idCol, array $menuIds): array
    {
        $menuIds = array_values(array_filter(array_map('intval', $menuIds)));
        if (!$menuIds || !$this->columnExists($table, 'id_menu')) {
            return [];
        }
        $rows = Db::getInstance()->executeS(
            'SELECT `' . bqSQL($idCol) . '` AS id FROM `' . _DB_PREFIX_ . bqSQL($table) . '`
            WHERE id_menu IN (' . implode(',', $menuIds) . ')'
        );

        return array_map('intval', array_column($rows ?: [], 'id'));
    }

    /**
     * @param int[] $menuIds
     *
     * @return int[]
     */
    private function blockIdsForMenus(array $menuIds): array
    {
        $menuIds = array_values(array_filter(array_map('intval', $menuIds)));
        if (!$menuIds) {
            return [];
        }
        $in = implode(',', $menuIds);
        $tabJoin = $this->columnExists('ets_mm_column', 'id_tab') && $this->tableExists('ets_mm_tab')
            ? 'LEFT JOIN `' . _DB_PREFIX_ . 'ets_mm_tab` t ON t.id_tab = c.id_tab'
            : '';
        $where = 'c.id_menu IN (' . $in . ')';
        if ($tabJoin !== '') {
            $where = '(' . $where . ' OR t.id_menu IN (' . $in . '))';
        }
        $rows = Db::getInstance()->executeS(
            'SELECT b.id_block FROM `' . _DB_PREFIX_ . 'ets_mm_block` b
            INNER JOIN `' . _DB_PREFIX_ . 'ets_mm_column` c ON c.id_column = b.id_column
            ' . $tabJoin . '
            WHERE ' . $where
        );

        return array_map('intval', array_column($rows ?: [], 'id_block'));
    }

    private function printReport(): void
    {
        echo "\nModifications : " . count($this->changes) . "\n";
        foreach ($this->changes as $change) {
            if ($change['field'] === 'content') {
                echo '- bloc ' . $change['id'] . ' : ' . $change['to'] . "\n";
                continue;
            }
            echo '- ' . $change['table'] . ' #' . $change['id'] . ' ' . $change['field']
                . ' : «' . $change['from'] . '» → «' . $change['to'] . "»\n";
        }
        $ambiguous = [];
        foreach ($this->ambiguous as $bucket => $items) {
            foreach (array_keys($items) as $fr) {
                $ambiguous[$fr] = $bucket;
            }
        }
        if ($ambiguous) {
            echo "\nLibellés ambigus (non copiés) : " . count($ambiguous) . "\n";
            foreach ($ambiguous as $fr => $bucket) {
                echo '- [' . $bucket . '] ' . $fr . "\n";
            }
        }
        if ($this->missing) {
            echo "\nSans équivalent anglais sur la boutique particulier : " . count($this->missing) . "\n";
            foreach (array_keys($this->missing) as $fr) {
                echo '- ' . $fr . "\n";
            }
        }
    }

    private function writeRollback(): void
    {
        $dir = _PS_ROOT_DIR_ . '/var/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = $dir . '/translate_pro_megamenu_en_' . date('Ymd-His') . '.jsonl';
        $fh = fopen($path, 'wb');
        if ($fh === false) {
            throw new RuntimeException('Rollback impossible : ' . $path);
        }
        foreach ($this->rollback as $row) {
            fwrite($fh, json_encode($row, JSON_UNESCAPED_UNICODE) . "\n");
        }
        fclose($fh);
        echo 'Rollback : ' . $path . "\n";
    }

    private function clearMenuCache(): void
    {
        $dir = _PS_MODULE_DIR_ . 'ets_megamenu/cache/';
        $n = 0;
        foreach (glob($dir . 'cache.*') ?: [] as $file) {
            if (is_file($file) && @unlink($file)) {
                $n++;
            }
        }
        echo "Cache Mega Menu vidé ({$n} fichier(s)).\n";
    }

    private function clearPageCache(): void
    {
        if (!Module::isEnabled('pagecache')) {
            return;
        }
        $file = _PS_MODULE_DIR_ . 'pagecache/pagecache.php';
        if (!class_exists('PageCache', false) && is_file($file)) {
            require_once $file;
        }
        if (class_exists('PageCache', false) && method_exists('PageCache', 'clearCache')) {
            PageCache::clearCache('ckc-pro-megamenu-en');
            echo "Cache pages vidé.\n";
        }
    }

    private function tableExists(string $table): bool
    {
        $name = _DB_PREFIX_ . $table;
        $found = Db::getInstance()->getValue(
            'SELECT TABLE_NAME FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'' . pSQL($name) . '\''
        );

        return $found === $name;
    }

    private function columnExists(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        $found = Db::getInstance()->getValue(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = \'' . pSQL(_DB_PREFIX_ . $table) . '\'
              AND COLUMN_NAME = \'' . pSQL($column) . '\''
        );
        $cache[$key] = $found === $column;

        return $cache[$key];
    }
}
