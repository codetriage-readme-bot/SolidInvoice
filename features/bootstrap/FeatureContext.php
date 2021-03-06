<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Behat\Behat\Context\Context;
use SolidInvoice\ClientBundle\Entity\ContactType;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\CoreBundle\Entity\Version;
use SolidInvoice\MoneyBundle\Factory\CurrencyFactory;
use SolidInvoice\SettingsBundle\Entity\Setting;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class FeatureContext implements Context
{
    private const DEFAULT_SETTINGS = [
        'system/company/company_name' => 'SolidInvoice',
        'system/company/logo' => null,
        'system/company/currency' => CurrencyFactory::DEFAULT_CURRENCY,
        'quote/email_subject' => 'New Quotation - #{id}',
        'quote/bcc_address' => null,
        'invoice/email_subject' => 'New Invoice - #{id}',
        'invoice/bcc_address' => null,
        'email/from_name' => 'SolidInvoice',
        'email/from_address' => 'info@solidinvoice.org',
        'email/format' => 'both',
        'hipchat/auth_token' => null,
        'hipchat/room_id' => null,
        'hipchat/server_url' => 'https://api.hipchat.com',
        'hipchat/notify' => null,
        'hipchat/message_color' => 'yellow',
        'sms/twilio/number' => null,
        'sms/twilio/sid' => null,
        'sms/twilio/token' => null,
        'notification/client_create' => '{"email":true,"hipchat":false,"sms":false}',
        'notification/invoice_status_update' => '{"email":true,"hipchat":false,"sms":false}',
        'notification/quote_status_update' => '{"email":true,"hipchat":false,"sms":false}',
        'notification/payment_made' => '{"email":true,"hipchat":false,"sms":false}',
        'email/sending_options/transport' => 'sendmail',
        'email/sending_options/host' => null,
        'email/sending_options/user' => null,
        'email/sending_options/password' => null,
        'email/sending_options/port' => null,
        'email/sending_options/encryption' => null,
        'system/general/currency' => 'USD',
    ];

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @BeforeScenario @resetSchema
     */
    public function resetDatabase()
    {
        foreach ($this->doctrine->getManagers() as $entityManager) {
            $entityManager->flush(); // Ensure Entity manager is clean
            $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

            if (!empty($metadata)) {
                $tool = new SchemaTool($entityManager);
                $tool->dropSchema($metadata);
                $entityManager->clear();
                $tool->createSchema($metadata);
            }
        }

        $em = $this->doctrine->getManagerForClass(Version::class);

        $em->persist((new Version())->setVersion(SolidInvoiceCoreBundle::VERSION));

        foreach (self::DEFAULT_SETTINGS as $key => $value) {
            $em->persist((new Setting())->setKey($key)->setValue($value)->setType(TextType::class));
        }

        foreach (['address', 'email', 'phone', 'mobile'] as $type) {
            $em->persist((new ContactType())->setType($type)->setName($type)->setRequired(false));
        }

        $em->flush();
    }
}
