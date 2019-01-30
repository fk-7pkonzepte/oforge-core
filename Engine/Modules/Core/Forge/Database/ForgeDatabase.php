<?php

namespace Oforge\Engine\Modules\Core\Forge\Database;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\ORM\Tools\Setup;
use Oforge\Engine\Modules\Core\Statics;

/**
 * Class ForgeDatabase
 *
 * @package Oforge\Engine\Modules\Core\Models
 */
class ForgeDatabase {
    const PATH_CHACHE_FILE = ROOT_PATH . Statics::DB_CACHE_FILE;
    /** @var ForgeDatabase $instance */
    protected static $instance = null;
    /** @var EntityManager $entityManager */
    private $entityManager = null;
    /** @var SchemaTool $schemaTool */
    private $schemaTool = null;
    /** @var SchemaValidator $schemaValidator */
    private $schemaValidator = null;
    /** @var ClassMetadata[] $metaDataCollection */
    private $metaDataCollection = [];
    /**
     * @var array $schemata
     */
    private $schemata = [];

    protected function __construct() {
    }

    /**
     * @return ForgeDatabase
     */
    public static function getInstance() : ForgeDatabase {
        if (null === self::$instance) {
            self::$instance = new ForgeDatabase();
        }

        return self::$instance;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager() : EntityManager {
        return $this->entityManager;
    }

    /**
     * @return SchemaValidator
     */
    public function getSchemaValidator() : SchemaValidator {
        return $this->schemaValidator;
    }

    /**
     * @return SchemaTool
     */
    public function getSchemaTool() : SchemaTool {
        return $this->schemaTool;
    }

    /**
     * @param array $settings
     *
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     */
    public function init(Array $settings) {
        $config = Setup::createAnnotationMetadataConfiguration($settings['metadata_dirs'], $settings['dev_mode']);

        $annotationReader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($annotationReader, $settings['metadata_dirs']);
        $config->setMetadataDriverImpl($annotationDriver);

        $filesystemCache = new FilesystemCache($settings['cache_dir']);
        $config->setMetadataCacheImpl($filesystemCache);

        $this->entityManager = EntityManager::create($settings['connection'], $config);
        DiscriminatorEntryListener::register($this->entityManager);

        $this->schemaValidator = new SchemaValidator($this->entityManager);
        $this->schemaTool      = new SchemaTool($this->entityManager);
    }

    /**
     * Init model schemata.
     *
     * @param string[] $schemata
     * @param bool $forceInit
     */
    public function initModelSchemata($schemata, bool $forceInit = false) : void {
        if (empty($schemata)) {
            return;
        }
        if (empty($this->schemata)) {
            $this->loadSchemata();
        }
        $schemataAdded = [];
        foreach ($schemata as $schema) {
            if (!isset($this->schemata[$schema]) || $forceInit) {
                $this->addMetaData($schema);
                $schemataAdded[] = $schema;
            }
        }
        if (!empty($schemataAdded)) {
            $this->saveSchemata($schemataAdded);
        }
    }

    /**
     * Add new schema or update existing.
     *
     * @param string $schema
     */
    protected function addMetaData(string $schema) : void {
        $metaData = $this->entityManager->getClassMetadata($schema);

        $this->metaDataCollection[] = $metaData;

        $inSync = $this->schemaValidator->schemaInSyncWithMetadata();
        if (!$inSync) {
            $this->schemaTool->updateSchema($this->metaDataCollection, true);
        }
        $this->schemata[$schema] = 1;
    }

    /**
     * Load model schema from chache file.
     */
    private function loadSchemata() : void {
        if (file_exists(self::PATH_CHACHE_FILE)) {
            $this->schemata = [];
            if ($file = fopen(self::PATH_CHACHE_FILE, "r")) {
                while (!feof($file)) {
                    $line = trim(fgets($file));
                    if (!empty($line)) {
                        $this->schemata[$line] = 1;
                    }
                }
                fclose($file);
            }
        }
    }

    /**
     * Save model schema to chache file.
     *
     * @param string[] $schemataAdded
     */
    private function saveSchemata($schemataAdded) : void {
        $fileContent = implode("\n", $schemataAdded) . "\n";
        file_put_contents(self::PATH_CHACHE_FILE, $fileContent, FILE_APPEND);
    }

}
