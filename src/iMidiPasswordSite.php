<?php declare(strict_types=1);

namespace iMidiPasswordSite;

use Enqueue\Util\UUID;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;

class iMidiPasswordSite extends Plugin
{

    private const CUSTOM_FIELD_NAME = 'password_site';

    public function install(InstallContext $installContext): void
    {
        $this->createCustomFields($installContext);
        parent::install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext):void
    {
        if(!$uninstallContext->keepUserData()) {
            $this->removeCustomFields($uninstallContext);
        }
        parent::uninstall($uninstallContext);
    }

    private function createCustomFields(InstallContext $installContext)
    {
        $customFields = [
            [
                'name' => self::CUSTOM_FIELD_NAME,
                'active' => true,
                'global' => true,
                'config' => [
                    'label' => [
                        'en-GB' => 'Password Protected Page',
                        'de-DE' => 'Passwortgeschützte Seite'
                    ],
                ],
                'customFields' => [
                    [
                        'name' => 'password_site_password',
                        'type' => \Shopware\Core\System\CustomField\CustomFieldTypes::TEXT,
                        'config' => [
                            'label' => [
                                'en-GB' => 'Password',
                                'de-DE' => 'Passwort',
                            ]
                        ]
                    ]
                ],
                'relations' => [
                    [
                        'entityName' => 'category'
                    ]
                ]
            ]
        ];

        $customFieldSetRepository = $this->getCustomFieldRepository();

        if (!$this->customFieldsExist($customFieldSetRepository, $installContext->getContext())) {
            foreach ($customFields as $customFieldSet) {
                $customFieldSetRepository->upsert([$customFieldSet], $installContext->getContext());
            }
        }
    }

    private function removeCustomFields(UninstallContext $uninstallContext): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $fieldIds = $this->customFieldsExist($customFieldSetRepository, $uninstallContext->getContext());

        if ($fieldIds) {
            $customFieldSetRepository->delete(array_values($fieldIds->getData()), $uninstallContext->getContext());
        }
    }

    private function customFieldsExist(EntityRepository $customFieldSetRepository, Context $context): ?IdSearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('name', [self::CUSTOM_FIELD_NAME]));

        $ids = $customFieldSetRepository->searchIds($criteria, $context);

        return $ids->getTotal() > 0 ? $ids : null;
    }

    private function getCustomFieldRepository()
    {
        return $this->container->get('custom_field_set.repository');
    }

}
