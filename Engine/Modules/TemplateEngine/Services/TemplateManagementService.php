<?php
/**
 * Created by PhpStorm.
 * User: Matthaeus.Schmedding
 * Date: 07.11.2018
 * Time: 10:39
 */

namespace Oforge\Engine\Modules\TemplateEngine\Services;

use Oforge\Engine\Modules\Core\Exceptions\TemplateNotFoundException;
use Oforge\Engine\Modules\Core\Statics;
use Oforge\Engine\Modules\TemplateEngine\Abstracts\AbstractTemplate;
use Oforge\Engine\Modules\TemplateEngine\Models\Template\Template;

class TemplateManagementService {
    
    private $em;
    private $repo;
    
    public function __construct() {
        $this->em = Oforge()->DB()->getEntityManager();
        $this->repo = $this->em->getRepository(Template::class);
    }

    /**
     * @param $name
     * @throws TemplateNotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function activate($name) {
        /**
         * @var $templateToActivate Template
         */
        $templateToActivate = $this->repo->findOneBy(["name" => $name]);
        $activeTemplate = $this->repo->findOneBy(["active" => 1]);

        if (!isset($templateToActivate)) {
            throw new TemplateNotFoundException($name);
        }

        if (isset($activeTemplate)) {
            /**
             * @var $activeTemplate Template
             */
            $activeTemplate->setActive(false);
        }

        $templateToActivate->setActive(true);

        $this->em->persist($templateToActivate);
        $this->em->persist($activeTemplate);
        $this->em->flush();
    }

    /**
     * Check if the given template name $name is stored in the database. If not, store it in the DB.
     * @param $name string
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function register($name) {
        $template = $this->repo->findOneBy(["name" => $name]);
        
        if (!isset($template)) {
            $className = Statics::THEMES_DIR_NAME . "\\" . $name . "\\Template";
            $parent = null;
            
            if (is_subclass_of($className, AbstractTemplate::class)) {
                /**
                 * @var $instance AbstractTemplate
                 */
                $instance = new $className();
                $parent = $instance->parent;
            }
            
            if ($parent !== null) {
                /**
                 * @var $parentTemplate Template
                 */
                $parentTemplate = $this->repo->findOneBy(["name" => $parent]);
                $parent = $parentTemplate->getId();
            }
            
            $template = Template::create(array("name" => $name, "active" => 0, "installed" => 0, "parentId" => $parent));
            
            $this->em->persist($template);
            $this->em->flush();
        }
    }

    public function list() {
        $templateList = $this->repo->findAll();
        return $templateList;
    }

    /**
     * Get the active theme, delete old cached assets, build new assets
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function build() {
        /**
         * @var Template $template
         */
        $template = $this->repo->findOneBy(["active" => 1]);
        if ($template) {
            /**
             * @var TemplateAssetService $templateAssetService
             */
            $templateAssetService = Oforge()->Services()->get('assets.template');
            $templateAssetService->clear();
            $templateAssetService->build($templateAssetService::DEFAULT_SCOPE);
        }
    }
}
