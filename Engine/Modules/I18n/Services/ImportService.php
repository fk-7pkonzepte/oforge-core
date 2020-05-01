<?php

namespace Oforge\Engine\Modules\I18n\Services;

use Exception;
use Oforge\Engine\Modules\Core\Abstracts\AbstractDatabaseAccess;
use Oforge\Engine\Modules\Core\Helper\CsvHelper;
use Oforge\Engine\Modules\Core\Helper\StringHelper;
use Oforge\Engine\Modules\I18n\Models\Language;
use Oforge\Engine\Modules\I18n\Models\Snippet;

/**
 * Class ImportService
 *
 * @package Oforge\Engine\Modules\I18n\Services
 */
class ImportService extends AbstractDatabaseAccess {
    public function __construct() {
        parent::__construct([
            'language' => Language::class,
            'snippet'  => Snippet::class,
        ]);
    }

    /**
     * @param string $filepath Path to file, should be an absolute path if possible.
     * @param bool $groupedByKey Are snippet values grouped by key?
     *
     * @return array Returns empty Array if not supported file format or statistics array with items (number of processed items), skipped (skipped items with errors), created & updated & unchanched (created or updated or not changed snippets).
     * @throws Exception
     */
    public function import(string $filepath, bool $groupedByKey) : array {
        if (StringHelper::endsWith($filepath - strtolower($filepath), '.csv')) {
            return $this->importCSVInternal($filepath, $groupedByKey);
        } elseif (StringHelper::endsWith($filepath - strtolower($filepath), '.json')) {
            return $this->importJsonInternal($filepath, $groupedByKey);
        }

        return [];
    }

    /**
     * Creates / Updates Text-Snippets from given .csv file.<br>
     * A Text-Snippet consists of three parameters: Scope, Name and Value.<br>
     * Therefore a line in the .csv file should look like this (optional enclosure with double quotes): "scope";"name";"value".<br>
     * As of this point, the function can only be called from the console<br>
     * ( i.e. 'php /bin/console oforge:service:run i18n:importFromCsv mysnippets.csv' ).
     *
     * @param string $filepath Path to file, should be an absolute path if possible.
     *
     * @return array Statistics array with items (number of processed items), skipped (skipped items with errors), created & updated & unchanched (created or updated or not changed snippets)
     * @throws Exception
     */
    public function importCSV(string $filepath) : array {
        return $this->importCSVInternal($filepath, false);
    }

    /**
     * Creates / Updates Text-Snippets from given .csv file.<br>
     * The header line in the .csv file defines the languages to import.<br>
     * A line in the .csv file should contain:
     *      "Snippet-Key";"language1-Value";"language2-Value"<br>
     * Example:<br>
     * <pre>
     *      Key;en;de
     *      module_i18n_save_me;"Save me";"Mich abspeichern"
     * </pre>
     *
     * @param string $filepath Path to file, should be an absolute path if possible.
     *
     * @return array Statistics array with items (number of processed items), skipped (skipped items with errors), created & updated & unchanched (created or updated or not changed snippets)
     * @throws Exception
     */
    public function importGroupedCSV(string $filepath) : array {
        return $this->importCSVInternal($filepath, true);
    }

    /**
     * @param string $filepath Path to file, should be an absolute path if possible.
     *
     * @return array Statistics array with items (number of processed items), skipped (skipped items with errors), created & updated & unchanched (created or updated or not changed snippets)
     * @throws Exception
     */
    public function importJson(string $filepath) : array {
        return $this->importJsonInternal($filepath, false);
    }

    /**
     * @param string $filepath Path to file, should be an absolute path if possible.
     *
     * @return array Statistics array with items (number of processed items), skipped (skipped items with errors), created & updated & unchanched (created or updated or not changed snippets)
     * @throws Exception
     */
    public function importGroupedJson(string $filepath) : array {
        return $this->importJsonInternal($filepath, true);
    }

    /**
     * @param string $filepath
     * @param bool $groupedByKey Are snippet values grouped by key?
     *
     * @return array
     * @throws Exception
     */
    protected function importCSVInternal(string $filepath, bool $groupedByKey) {
        $options      = [
            'header-row' => false,
        ];
        $statistics   = [
            'items'     => 0,
            'skipped'   => 0,
            'created'   => 0,
            'updated'   => 0,
            'unchanged' => 0,
        ];
        $firstRow     = true;
        $languageIsos = [];

        if ($groupedByKey) {
            $rowCallable = function ($row) use ($filepath, &$statistics, &$firstRow, &$languageIsos) {
                if (!is_array($row) || empty($row)) {
                    // $statistics['skipped']++;
                    return;
                }

                if ($firstRow) {
                    for ($i = 1, $max = count($row); $i < $max; $i++) {
                        $languageIso      = $row[$i];
                        $languageIsos[$i] = $languageIso;
                    }
                    $firstRow = false;

                    return;
                }
                $statistics['items']++;

                $key    = $row[0];
                $values = [];
                foreach ($languageIsos as $index => $languageIso) {
                    $value                = $row[$index] ?? '';
                    $values[$languageIso] = $value;
                }
                $this->importSnippets($filepath, $statistics, $key, $values);
            };
        } else {
            $rowCallable = function ($row) use ($filepath, &$statistics, &$firstRow, &$languageIsos) {
                if (!is_array($row) || empty($row) || $firstRow) {
                    // $statistics['skipped']++;
                    return;
                }
                if (count($row) !== 3) {
                    $statistics['skipped']++;

                    return;
                }
                $statistics['items']++;

                $languageIso = $row[0];
                $key         = $row[1];
                $value       = $row[2];
                if (!in_array($languageIso, $languageIsos)) {
                    $languageIsos[] = $languageIso;
                }
                $this->importSnippets($filepath, $statistics, $key, [
                    $languageIso => $value,
                ]);
            };
        }
        try {
            CsvHelper::read($filepath, $rowCallable, $options);
        } catch (Exception $exception) {
            Oforge()->Logger()->logException($exception);
            throw $exception;
        }
        foreach ($languageIsos as $languageIso) {
            $this->importLanguage($languageIso, $statistics);
        }

        return $statistics;
    }

    /**
     * @param string $filepath
     * @param bool $groupedByKey
     *
     * @return array
     * @throws Exception
     */
    protected function importJsonInternal(string $filepath, bool $groupedByKey) : array {
        if (!file_exists($filepath)) {
            throw new Exception("File '$filepath' does not exist.");
        }
        if (!is_readable($filepath)) {
            throw new Exception("File '$filepath' is not readable.1");
        }
        $filepath = realpath($filepath);
        if (!file_exists($filepath)) {
            throw new Exception("File '$filepath' does not exist.");
        }
        if (!is_readable($filepath)) {
            throw new Exception("File '$filepath' is not readable.2");
        }
        $content = file_get_contents($filepath);
        $items   = json_decode($content, true);
        if (!is_array($items)) {
            throw new Exception('File content is an array.');
        }
        $statistics   = [
            'items'     => 0,
            'skipped'   => 0,
            'created'   => 0,
            'updated'   => 0,
            'unchanged' => 0,
        ];
        $languageIsos = [];
        foreach ($items as $item) {
            if ($groupedByKey) {
                $values = $item['values'];
            } else {
                $values = [
                    'iso'   => $item['iso'],
                    'value' => $item['value'],
                ];
            }
            $this->importSnippets($filepath, $statistics, $item['key'], $values);
        }
        foreach ($languageIsos as $languageIso) {
            $this->importLanguage($languageIso, $statistics);
        }

        return $statistics;
    }

    /**
     * @param string $filepath
     * @param array $statistics
     * @param string $key
     * @param array $values
     */
    protected function importSnippets(string $filepath, array &$statistics, string $key, array $values) {
        if (empty($key)) {
            Oforge()->Logger()->get()->error('Empty i18n snippet key in file: ' . $filepath);
            $statistics['skipped']++;

            return;
        }
        foreach ($values as $languageIso => $value) {
            try {
                /** @var Snippet $snippet */
                $snippet = $this->repository('snippet')->findOneBy([
                    'scope' => $languageIso,
                    'name'  => $key,
                ]);
                if ($snippet === null) {
                    if (empty($value) && $value !== 0) {
                        $value = $key;
                    }
                    $snippet = Snippet::create([
                        'scope' => $languageIso,
                        'name'  => $key,
                        'value' => $value,
                    ]);
                    $this->entityManager()->create($snippet);
                    $statistics['created']++;
                } elseif (!empty($value) && $value !== $key && $snippet->getValue() === $key) {
                    $snippet->setValue($value);
                    $this->entityManager()->update($snippet);
                    $statistics['updated']++;
                } else {
                    $statistics['unchanged']++;
                }
            } catch (Exception $exception) {
                Oforge()->Logger()->logException($exception);
                $statistics['skipped']++;
            }
        }
    }

    /**
     * @param string $languageIso
     * @param array $statistics
     */
    protected function importLanguage(string $languageIso, array &$statistics) : void {
        try {
            /** @var Language $language */
            $language = $this->repository('language')->findOneBy([
                'iso' => $languageIso,
            ]);
            if ($language === null) {
                $this->entityManager()->create(Language::create([
                    'iso'  => $languageIso,
                    'name' => $languageIso,
                ]));
                $statistics['created']++;
            }
        } catch (Exception $exception) {
            Oforge()->Logger()->logException($exception);
        }
    }

}
