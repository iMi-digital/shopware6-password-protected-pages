<?php declare(strict_types=1);

namespace iMidiPasswordSite;

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

        foreach ($customFields as $customFieldSet) {
            $repo->upsert([$customFieldSet], $context->getContext());
        }
    }
}
