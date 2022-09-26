<?php declare(strict_types=1);

namespace iMidiPasswordSite;

use Enqueue\Util\UUID;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;

class iMidiPasswordSite extends Plugin
{
    public function install(InstallContext $context): void {
        $this->createCustomFields($context);
    }

    private function createCustomFields(InstallContext $context)
    {
        $customFields = [
            [
                'name' => 'password_site',
                'active' => true,
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
        $repo = $this->container->get('custom_field_set.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'password_site'));

        $result = $repo->search($criteria, $context->getContext());

        if($result->count() <= 0) {
            foreach ($customFields as $customFieldSet) {
                $repo->upsert([$customFieldSet], $context->getContext());
            }
        }
    }
}
