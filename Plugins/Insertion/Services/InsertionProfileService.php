<?php

namespace Insertion\Services;

use FrontendUserManagement\Models\User;
use Insertion\Models\InsertionProfile;
use Oforge\Engine\Modules\Core\Abstracts\AbstractDatabaseAccess;
use Oforge\Engine\Modules\Media\Services\MediaService;

class InsertionProfileService extends AbstractDatabaseAccess {
    public function __construct() {
        parent::__construct([
            'default' => InsertionProfile::class,
        ]);
    }

    /**
     * @param int $user
     *
     * @return InsertionProfile|null
     * @throws \Doctrine\ORM\ORMException
     */
    public function get(int $user) : ?InsertionProfile {
        /**
         * @var $result InsertionProfile
         */
        $result = $this->repository()->findOneBy(["user" => $user]);

        return $result;
    }

    public function update(User $user, array $params) {
        /**
         * @var $result InsertionProfile
         */
        $result = $this->repository()->findOneBy(["user" => $user]);

        /** @var MediaService $mediaService */
        $mediaService = Oforge()->Services()->get('media');

        $create = $result == null;

        if ($create) {
            $result = new InsertionProfile();
        }

        if (isset($_FILES["background"])) {
            $media = $mediaService->add($_FILES["background"]);
            if ($media != null) {
                $oldId = null;

                if ($result->getBackground() != null) {
                    $oldId = $result->getBackground()->getId();
                }

                $result->setBackground($media);

                if ($oldId != null) {
                    $mediaService->delete($oldId);
                }
            }
        }
        if (isset($_FILES["profile"])) {
            $media = $mediaService->add($_FILES["profile"]);
            if ($media != null) {
                $oldId = null;

                if ($result->getMain() != null) {
                    $oldId = $result->getMain()->getId();
                }

                $result->setMain($media);

                if ($oldId != null) {
                    $mediaService->delete($oldId);
                }
            }
        }

        $result->fromArray([
            "description"          => $params["description"],
            "user"                 => $user,
            "imprintName"          => $params["imprint_name"],
            "imprintStreet"        => $params["imprint_street"],
            "imprintZipCity"       => $params["imprint_zipCity"],
            "imprintPhone"         => $params["imprint_phone"],
            "imprintEmail"         => $params["imprint_email"],
            "imprintCompanyTaxId"  => $params["imprint_company_tax"],
            "imprintCompanyNumber" => $params["imprint_company_number"],
        ]);


        if ($create) {
            $this->entityManager()->create($result);
        } else {
            $this->entityManager()->update($result);
        }
    }

}
