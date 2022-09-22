<?php declare(strict_types=1);

namespace Melv\AutoExchangeRates;

use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class MelvAutoExchangeRates extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $currencyCustomFieldSetUuid = Uuid::randomHex();

        $customFieldSetRepository->upsert([
            [
                'id' => $currencyCustomFieldSetUuid,
                'name' => 'melv_currency',
                'config' => [
                    'label' => [
                        'en-GB' => 'Currency Exchange',
                        'nl-NL' => 'Currency Exchange',
                        'de-DE' => 'Currency Exchange'
                    ]
                ],
                'customFields' => [
                    [
                        'id' => Uuid::randomHex(),
                        'name' => 'melv_currency_safety_margin',
                        'type' => CustomFieldTypes::FLOAT,
                        'config' => [
                            'componentName' => 'sw-field',
                            'customFieldType' => 'number',
                            'customFieldPosition' => 1,
                            'numberType' => 'float',
                            'label' => [
                                'en-GB' => 'Safe margin %',
                                'nl-NL' => 'Safe margin %',
                                'de-DE' => 'Safe margin %'
                            ]
                        ]
                    ]
                ],
                'relations' => [
                    [
                        'id' => $currencyCustomFieldSetUuid,
                        'entityName' => $this->container->get(CurrencyDefinition::class)->getEntityName()
                    ],
                ]
            ],
        ], new Context(new SystemSource()));
    }
}
