<?php

namespace App\Http\Requests;

use App\Services\OccurrenceExpander;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DateRangeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->fromDate()->diffInDays($this->toDate()) >= OccurrenceExpander::MAX_RANGE_DAYS) {
                    $validator->errors()->add('to', 'El rango no puede superar '.OccurrenceExpander::MAX_RANGE_DAYS.' días.');
                }
            },
        ];
    }

    public function fromDate(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->string('from')->toString());
    }

    public function toDate(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->string('to')->toString());
    }
}
