<?php

namespace HS\Domain\Workspace;

use Illuminate\Database\Eloquent\Collection;

class EventCollection extends Collection
{
    // Possibly use closures here to resolve field and label
    protected $fieldTypeLabel = [
        'select' => 'sValue',
        'checkbox' => 'sValue',
        'lrgtext' => 'sValue',
        'text' => 'sValue',
        'regex' => 'sValue',
        'ajax' => 'sValue',
        'drilldown' => 'sValue',

        'date' => 'iValue',
        'datetime' => 'iValue',
        'numtext' => 'iValue',

        'decimal' => 'dValue',
    ];

    /**
     * Add an Event to the collection.
     *
     * @param  Event  $item
     * @return $this
     */
    public function add($item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Save events to storage.
     *
     * @param $xRequestHistory
     * @return bool
     * @throws \Exception
     */
    public function flush($xRequestHistory)
    {
        return app('db')->transaction(function () use ($xRequestHistory) {
            foreach ($this->items as $event) {
                $event->xRequestHistory = $xRequestHistory;
                $event->save();
            }

            return true;
        });
    }

    /**
     * Transform a raw request array
     * to a collection of Request Events.
     * @param array $request
     * @param int $xRequestHistory
     * @return static
     */
    public static function toCollection(array $request, $xRequestHistory)
    {
        $collection = new static;

        /*
         * Reporting Tags
         * Ensures set tags in request are valid
         */
        if (isset($request['reportingTags']) && is_array($request['reportingTags']) && is_numeric($request['xCategory'])) {
            $categoryReportingTags = apiGetReportingTags($request['xCategory']);
            $tagIds = array_keys($categoryReportingTags);
            $usedTagNames = [];
            $usedTagIds = [];

            foreach ($request['reportingTags'] as $tagId) {
                if (in_array($tagId, $tagIds)) {
                    $usedTagNames[] = trim($categoryReportingTags[$tagId]);
                    $usedTagIds[] = (int) $tagId;
                }
            }

            $collection->add(new Event([
                'xRequest' => $request['xRequest'],
                'xRequestHistory' => $xRequestHistory,
                'xPerson' => $request['xPersonOpenedBy'],
                'dtLogged' => time(),
                'sColumn' => 'xReportingTag',
                'sValue' => json_encode($usedTagIds),
                'sLabel' => implode(',', $usedTagNames),
            ]));
        }

        /*
         * Custom Fields
         * Not set on initial install
         */
        if (isset($GLOBALS['customFields']) && is_array($GLOBALS['customFields'])) {
            foreach ($GLOBALS['customFields'] as $customField) {
                $fId = 'Custom'.$customField['fieldID'];

                // Only record custom fields with values
                if (! empty($request[$fId])) {
                    $value = $request[$fId];

                    // Truncate large text fields
                    if ($customField['fieldType'] == 'lrgtext') {
                        $value = utf8_substr($value, 0, 140);
                    }

                    // Select which column to save to
                    $column = 'sValue';

                    if(in_array($customField['fieldType'], ['date', 'datetime', 'numtext'])) {
                        $column = 'iValue';
                    }

                    if($customField['fieldType'] == 'decimal') {
                        $column = 'dValue';
                    }

                    $collection->add(new Event([
                        'xRequest' => $request['xRequest'],
                        'xRequestHistory' => $xRequestHistory,
                        'xPerson' => $request['xPersonOpenedBy'],
                        'dtLogged' => time(),
                        'sColumn' => $fId,
                        $column => $value,
                        'sLabel' => $collection->eventFieldLabel($customField, $value),
                    ]));
                }
            }
        }

        /**
         * Get some data to fill these out.
         */

        // Passthru value function
        $passthru = function ($value) {
            return trim($value);
        };

        // Get staffers
        $staffResult = apiGetAllUsersComplete();
        $staffers = [];
        foreach ($staffResult as $id => $staffer) {
            $staffers[$id] = $staffer['fullname'];
        }
        $staffers[0] = utf8_strtoupper(lg_inbox);

        // Get Categories
        $carResult = apiGetAllCategoriesComplete();
        $categories = [];
        while ($cat = $carResult->FetchRow()) {
            $categories[$cat['xCategory']] = $cat['sCategory'];
        }

        // Set fields and function to get values
        $requestFields = [
            'sUserId' => $passthru,
            'sFirstName' => $passthru,
            'sLastName' => $passthru,
            'sEmail' => $passthru,
            'sPhone' => $passthru,
            'sTitle' => $passthru,

            'fOpen' => function ($fOpen) {
                return boolShow($fOpen, lg_isopen, lg_isclosed);
            },
            'xPersonAssignedTo' => function ($xPerson) use ($staffers) {
                return $staffers[$xPerson];
            },
            'xStatus' => function ($xStatus) {
                return (isset($GLOBALS['reqStatus'][$xStatus]) && ! is_null($GLOBALS['reqStatus'][$xStatus]))
                    ? $GLOBALS['reqStatus'][$xStatus]
                    : 'Active';
            },
            'fUrgent' => function ($fUrgent) {
                return boolShow($fUrgent, lg_isurgent, lg_isnormal);
            },
            'xCategory' => function ($xCategory) use ($categories) {
                return $categories[$xCategory];
            },
            'xMailboxToSendFrom' => function ($xMailbox) {
                if ($xMailbox == 0) {
                    return lg_default_mailbox;
                }

                $mailbox = apiGetMailbox($xMailbox);

                return $mailbox['sReplyEmail'];
            },
            'fTrash' => function ($fTrash) {
                return ($fTrash == 1)
                    ? lg_lookup_19
                    : lg_lookup_20;
            },
        ];

        foreach ($requestFields as $field => $valueFn) {
            // Only record fields with values
            if (empty($request[$field])) {
                continue;
            }

            // We only use [s]tring or [i]nteger value types, no decimals for these
            $fieldType = (utf8_substr($field, 0, 1) == 's') ? 'sValue' : 'iValue';

            $sLabel = $valueFn($request[$field]);

            if (is_null($sLabel)) {
                $sLabel = '';
            }

            $collection->add(new Event([
                'xRequest' => $request['xRequest'],
                'xRequestHistory' => $xRequestHistory,
                'xPerson' => $request['xPersonOpenedBy'],
                'dtLogged' => time(),
                'sColumn' => $field,
                $fieldType => $request[$field],
                'sLabel' => $sLabel,
                // sDescription is null on initial value
            ]));
        }

        return $collection;
    }

    /**
     * Return proper column type to save to event log.
     * @param $fieldType
     * @return string
     */
    public function eventFieldType($fieldType)
    {
        return isset($this->fieldTypeLabel[$fieldType])
            ? $this->fieldTypeLabel[$fieldType]
            : 'sValue';
    }

    /**
     * Handle special needs for the event log label
     * per field type.
     * @param $customField
     * @param $fieldValue
     * @return string
     */
    public function eventFieldLabel($customField, $fieldValue)
    {
        if ($customField['fieldType'] == 'lrgtext') {
            return utf8_substr($customField['fieldName'], 0, 75);
        }

        if ($customField['fieldType'] == 'drilldown') {
            return cfDrillDownFormat(trim($fieldValue));
        }

        if ($customField['fieldType'] == 'date' || $customField['fieldType'] == 'datetime') {
            $time_format = $customField['fieldType'] == 'date' ? hs_setting('cHD_POPUPCALSHORTDATEFORMAT') : hs_setting('cHD_POPUPCALDATEFORMAT');

            return hs_showCustomDate($fieldValue, $time_format);
        }

        return $fieldValue;
    }
}
