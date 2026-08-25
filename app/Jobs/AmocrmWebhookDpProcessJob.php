<?php

namespace App\Jobs;

use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\NoteType\ServiceMessageNote;
use App\Notifications\NotificationProcessor;
use App\Prototypes\AmocrmWebhookSettingsBag;
use Makeroi\Amocrm\Enums\EntityType;
use Makeroi\Amocrm\Enums\EventType;
use Makeroi\Amocrm\Jobs\BaseWebhookDpProcessJob;
use Makeroi\Amocrm\Kernel\Auth\KAuth;
use Makeroi\Amocrm\Prototypes\WebhookDpDataPrototype;
use Makeroi\Amocrm\Services\Notes;
use Makeroi\Amocrm\Services\WidgetHook;

class AmocrmWebhookDpProcessJob extends BaseWebhookDpProcessJob
{
    public function handle(): int
    {
        WidgetHook::build($this->data)
            ->setSettingsClass(AmocrmWebhookSettingsBag::class)
            ->register(EntityType::Lead(), EventType::Any(), function(WebhookDpDataPrototype $data) {
                if (!KAuth::getInstall()->isActiveStatus()) {
                    KAuth::getApiClient()->notes(EntityTypesInterface::LEADS)->addOne((new ServiceMessageNote())
                        ->setEntityId($data->getEntityId())
                        ->setText("Не отправили оповещение. Виджет имеет некорректный статус")
                    );
                    logger()->debug('У виджета не активный статус');
                    return;
                }
                $accountId = KAuth::getAccount()->getAmocrmId();
                (new NotificationProcessor($this->getEntityId(), $this->getSettings(), $accountId))->handle();
            })
            ->register(EntityType::Customer(), EventType::Any(), function (WebhookDpDataPrototype $data) {
                (new Notes(KAuth::getApiClient()))->addToCustomer($data->getEntityId(),'Интеграция не поддерживает работу с покупателями');
            })
            ->handle();
        return 0;
    }
}
