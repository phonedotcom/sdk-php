<?php namespace PhoneCom\Sdk;

use PhoneCom\Mason\Builder\Child;
use PhoneCom\Sdk\Models\Model;

class Sms extends Model
{
    protected $pathInfo = '/sms';

    /**
     * @return array List of SMS test messages created
     */
    public static function create(array $attributes = [])
    {
        $model = new static($attributes);

        return $model->save();
    }

    public function save()
    {
        if (empty($this->attributes['id'])) {
            return $this->hydrate($this->newQuery()->insert($this->attributes)[0]->items);
        }

        return $this->newQuery()->where('id', 'eq', $this->attributes['id'])->update($this->attributes);
    }

    public function toFullMason()
    {
        return (new Child([
                'id' => (int)$this->id,
                'created' => ($this->created === null ? null : (float)$this->created),
                'scheduled' => ($this->scheduled === null ? null : (int)$this->scheduled),
                'direction' => $this->direction,
                'from' => $this->from,
                'to' => $this->to,
                'content' => $this->content
            ]))
            ->setControl('self', ['href' => $this->getSelfUrl()]);
    }

    public static function getBriefOutputSchema()
    {
        // TODO: What should we use in place of GetSharedDefinitions???  We break MVC if we refer to a controller
        // TODO: from within a model.

        // TODO: Also, using $ref does not give us a chance to add a title property so we can give more descriptions.
        // TODO: The title and description need to go in the Definitions also.  But we need to work through the
        // TODO: implications of this because it might mean duplicating or renaming some definitions in order to
        // TODO: have the titles and descriptions be applicable in all cases where they are used.

        return parent::getBriefOutputSchema()
            ->setRequiredProperty('created', 'number', [
                'title' => 'Timestamp when the record was created in Phone.com\'s system. UNIX timestamp, including '
                    . 'decimal fractions of a second.'
            ])
            ->setRequiredProperty('schedule', ['integer', 'null'], [
                'title' => 'Earliest timestamp after which it was requested that this message be sent. UNIX timestamp.'
            ])
            ->setRequiredPropertyRef('from', GetSharedDefinitions::getUrl('phone-number'))
            ->setRequiredPropertyRef('to', GetSharedDefinitions::getUrl('phone-number'))
            ->setRequiredProperty('direction', 'string', [
                'title' => 'Direction of travel for this message',
                'description' => 'Outbound if this record was created in the process of originating a message. '
                    . 'Inbound if in the process of receiving it.',
                'enum' => ['outbound', 'inbound']
            ])
            ->setRequiredProperty('content', 'string', [
                'title' => 'Body of the text message',
                'maxLength' => 160
            ]);
    }
}
