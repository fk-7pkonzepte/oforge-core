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
    
    private $entityManager;
    private $repository;
    
    public function __construct() {
        $this->entityManager = Oforge()->DB()->getEntityManager();
        $this->repository = $this->entityManager->getRepository(Template::class);
    }

    /**
     * @param $name
     *
     * @throws TemplateNotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function activate($name) {
        /** @var $templateToActivate Template */
        $templateToActivate = $this->repository->findOneBy(["name" => $name]);
        $activeTemplate = $this->getActiveTemplate();

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

        $this->entityManager->persist($templateToActivate);
        $this->entityManager->persist($activeTemplate);
        $this->entityManager->flush();
    }

    /**
     * Check if the given template name $name is stored in the database. If not, store it in the DB.
     * @param string $name
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     * @throws \Oforge\Engine\Modules\TemplateEngine\Exceptions\InvalidScssVariableException
     * @throws TemplateNotFoundException
     */
    public function register($name) {
        $template = $this->repository->findOneBy(["name" => $name]);
        
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
                $parentTemplate = $this->repository->findOneBy(["name" => $parent]);
                $parent = $parentTemplate->getId();
            }
            
            $template = Template::create(array("name" => $name, "active" => 0, "installed" => 0, "parentId" => $parent));
            
            $this->entityManager->persist($template);
            $this->entityManager->flush();

            $instance->registerTemplateVariables();
        }
    }

    /**
     * @return Template[]
     */
    public function list() {
        return $this->repository->findAll();
    }

    /**
     * Get the active theme, delete old cached assets, build new assets
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     * @throws \Oforge\Engine\Modules\TemplateEngine\Exceptions\InvalidScssVariableException*@throws TemplateNotFoundException
     * @throws TemplateNotFoundException
     */
    public function build() {
        /** @var Template $template */
        $template = $this->getActiveTemplate();
        if ($template) {
            /** @var TemplateAssetService $templateAssetService */
            $templateAssetService = Oforge()->Services()->get('assets.template');
            $templateAssetService->clear();

            $className = Statics::TEMPLATES_DIR_NAME . "\\" . $template->getName() . "\\Template";

            if (is_subclass_of($className, AbstractTemplate::class)) {
                /** @var $instance AbstractTemplate */
                $instance = new $className();
                $instance->registerTemplateVariables();
            }

            $templateAssetService->build($template->getName(), $templateAssetService::DEFAULT_SCOPE);
        }
    }

    /**
     * @return Template
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws TemplateNotFoundException
     */
    public function getActiveTemplate()
    {
        /**
         * @var $template Template
         */
        $template = $this->repository->findOneBy(["active" => 1]);
        if ($template === null) {
            $template = $this->repository->findOneBy(["name" => "Base"]);

            if ($template === null) {
                throw new TemplateNotFoundException("Base");
            }

            $template->setActive(1);
            $this->entityManager->flush();
        }
        return $template;
    }
}
