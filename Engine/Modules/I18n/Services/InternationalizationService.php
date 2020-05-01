<?php

namespace Oforge\Engine\Modules\I18n\Services;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Oforge\Engine\Modules\Core\Abstracts\AbstractDatabaseAccess;
use Oforge\Engine\Modules\Core\Helper\CsvHelper;
use Oforge\Engine\Modules\I18n\Models\Snippet;

/**
 * Class InternationalizationService
 *
 * @package Oforge\Engine\Modules\I18n\Services
 */
class InternationalizationService extends AbstractDatabaseAccess {
    /** @var string[] $currentActiveLanguages */
    private $currentActiveLanguages;
    /** @var array $cache */
    private $cache;

    public function __construct() {
        parent::__construct(Snippet::class);
    }

    /**
     * @param string $key
     * @param string $language
     * @param string|array|null $defaultValue
     *
     * @return string
     */
    public function get(string $key, string $language, $defaultValue = null) : string {
        if (!isset($this->currentActiveLanguages)) {
            /** @var LanguageService $languageService */
            $languageService              = Oforge()->Services()->get('i18n.language');
            $this->currentActiveLanguages = array_keys($languageService->getFilterDataLanguages(true));
        }
        if ($defaultValue === null) {
            $defaultValue = array_fill_keys($this->currentActiveLanguages, $key);
        } elseif (is_string($defaultValue)) {
            $defaultValue = array_fill_keys($this->currentActiveLanguages, $defaultValue);
        }
        if (!isset($defaultValue[$language])) {
            $defaultValue[$language] = $key;
        }
        foreach ($defaultValue as $languageISO => $value) {
            if (!isset($this->cache[$languageISO][$key])) {
                try {
                    /** @var Snippet $snippet */
                    $snippet = $this->repository()->findOneBy([
                        'name'  => $key,
                        'scope' => $languageISO,
                    ]);
                    if (!isset($snippet)) {
                        $snippet = Snippet::create([
                            'name'  => $key,
                            'scope' => $languageISO,
                            'value' => $value,
                        ]);
                        $this->entityManager()->create($snippet);
                    }
                    if (!isset($this->cache[$languageISO])) {
                        $this->cache[$languageISO] = [];
                    }
                    $this->cache[$languageISO][$key] = $snippet->getValue();
                } catch (ORMException $exception) {
                    Oforge()->Logger()->logException($exception);
                    $this->cache[$languageISO][$key] = $key;
                }
            }
        }

        return $this->cache[$language][$key];
    }

    /**
     * @param string $key
     * @param string $language
     *
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function exists(string $key, string $language) : bool {
        if (!isset($this->cache[$key])) {
            /** @var Snippet $snippet */
            $snippet = $this->repository()->findOneBy([
                'name'  => $key,
                'scope' => $language,
            ]);

            return isset($snippet);
        }

        return true;
    }

}
