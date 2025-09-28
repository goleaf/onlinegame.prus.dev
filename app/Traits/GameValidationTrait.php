<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Intervention\Validation\Rules\Base64;
use Intervention\Validation\Rules\Bic;
use Intervention\Validation\Rules\CreditCard;
use Intervention\Validation\Rules\DataUri;
use Intervention\Validation\Rules\Ean;
use Intervention\Validation\Rules\Hexadecimalcolor;
use Intervention\Validation\Rules\Hsv;
use Intervention\Validation\Rules\Iban;
use Intervention\Validation\Rules\Isbn;
use Intervention\Validation\Rules\Jwt;
use Intervention\Validation\Rules\Latitude;
use Intervention\Validation\Rules\Longitude;
use Intervention\Validation\Rules\Postalcode;
use Intervention\Validation\Rules\Ulid;
use Intervention\Validation\Rules\Username;
use JonPurvis\Squeaky\Rules\Clean;
use Propaganistas\LaravelPhone\Rules\Phone;

trait GameValidationTrait
{
    /**
     * Validate game-specific data
     */
    protected function validateGameData(array $data, array $rules = [])
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate village coordinates
     */
    protected function validateVillageCoordinates($x, $y, $worldId = null)
    {
        $rules = [
            'x' => 'required|integer|min:0|max:999',
            'y' => 'required|integer|min:0|max:999',
        ];

        if ($worldId) {
            $rules['world_id'] = 'required|exists:worlds,id';
        }

        return $this->validateGameData([
            'x' => $x,
            'y' => $y,
            'world_id' => $worldId,
        ], $rules);
    }

    /**
     * Validate geographic coordinates (latitude/longitude)
     */
    protected function validateGeographicCoordinates($latitude, $longitude)
    {
        $rules = [
            'latitude' => ['required', new Latitude()],
            'longitude' => ['required', new Longitude()],
        ];

        return $this->validateGameData([
            'latitude' => $latitude,
            'longitude' => $longitude,
        ], $rules);
    }

    /**
     * Validate color values (hexadecimal)
     */
    protected function validateColorValue($color)
    {
        $rules = [
            'color' => ['required', new Hexadecimalcolor()],
        ];

        return $this->validateGameData(['color' => $color], $rules);
    }

    /**
     * Validate troop quantities
     */
    protected function validateTroopQuantities(array $troops)
    {
        $rules = [];
        foreach ($troops as $troopId => $quantity) {
            $rules["troops.{$troopId}"] = 'required|integer|min:0|max:10000';
        }

        return $this->validateGameData(['troops' => $troops], $rules);
    }

    /**
     * Validate resource amounts
     */
    protected function validateResourceAmounts(array $resources)
    {
        $rules = [
            'wood' => 'integer|min:0|max:999999999',
            'clay' => 'integer|min:0|max:999999999',
            'iron' => 'integer|min:0|max:999999999',
            'crop' => 'integer|min:0|max:999999999',
        ];

        return $this->validateGameData($resources, $rules);
    }

    /**
     * Validate movement data
     */
    protected function validateMovementData(array $data)
    {
        $rules = [
            'from_village_id' => 'required|exists:villages,id',
            'to_village_id' => 'required|exists:villages,id|different:from_village_id',
            'type' => 'required|in:attack,reinforce,support,return',
            'troops' => 'required|array|min:1',
            'troops.*' => 'integer|min:1|max:10000',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate battle data
     */
    protected function validateBattleData(array $data)
    {
        $rules = [
            'attacker_id' => 'required|exists:players,id',
            'defender_id' => 'required|exists:players,id|different:attacker_id',
            'attacker_village_id' => 'required|exists:villages,id',
            'defender_village_id' => 'required|exists:villages,id',
            'attacker_troops' => 'required|array|min:1',
            'defender_troops' => 'required|array|min:1',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate task data
     */
    protected function validateTaskData(array $data)
    {
        $rules = [
            'title' => ['required', 'string', 'max:255', new Clean()],
            'description' => ['nullable', 'string', 'max:1000', new Clean()],
            'type' => 'required|in:building,combat,resource,exploration,alliance',
            'status' => 'required|in:available,active,completed,expired',
            'progress' => 'integer|min:0|max:100',
            'target' => 'required|integer|min:1|max:999999',
            'rewards' => 'nullable|json',
            'deadline' => 'nullable|date|after:now',
            'world_id' => 'required|exists:worlds,id',
            'player_id' => 'required|exists:players,id',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate player data
     */
    protected function validatePlayerData(array $data)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', 'unique:players,name', new Username(), new Clean()],
            'tribe' => 'required|in:roman,teuton,gaul',
            'alliance_id' => 'nullable|exists:alliances,id',
            'world_id' => 'required|exists:worlds,id',
            'user_id' => 'required|exists:users,id|unique:players,user_id',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate alliance data
     */
    protected function validateAllianceData(array $data)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', 'unique:alliances,name', new Username(), new Clean()],
            'tag' => ['required', 'string', 'max:10', 'unique:alliances,tag', new Username(), new Clean()],
            'description' => ['nullable', 'string', 'max:1000', new Clean()],
            'world_id' => 'required|exists:worlds,id',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate phone number data
     */
    protected function validatePhoneData(array $data, $country = null)
    {
        $rules = [
            'phone' => ['nullable', 'string'],
            'phone_country' => ['nullable', 'string', 'size:2'],
        ];

        if (! empty($data['phone'])) {
            if ($country) {
                $rules['phone'][] = (new Phone())->country($country);
            } else {
                $rules['phone'][] = new Phone();
            }
            $rules['phone_country'][] = 'required_with:phone';
        }

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate building data
     */
    protected function validateBuildingData(array $data)
    {
        $rules = [
            'village_id' => 'required|exists:villages,id',
            'building_type_id' => 'required|exists:building_types,id',
            'level' => 'required|integer|min:0|max:20',
            'is_under_construction' => 'boolean',
            'construction_started_at' => 'nullable|date',
            'construction_completed_at' => 'nullable|date|after:construction_started_at',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate quest data
     */
    protected function validateQuestData(array $data)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', new Clean()],
            'description' => ['required', 'string', 'max:1000', new Clean()],
            'category' => 'required|in:tutorial,main,side,daily,weekly,special',
            'difficulty' => 'required|in:easy,medium,hard,expert',
            'requirements' => 'nullable|json',
            'rewards' => 'nullable|json',
            'is_repeatable' => 'boolean',
            'cooldown_hours' => 'nullable|integer|min:0|max:168',  // 1 week max
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate achievement data
     */
    protected function validateAchievementData(array $data)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', new Clean()],
            'description' => ['required', 'string', 'max:1000', new Clean()],
            'category' => 'required|in:combat,building,resource,exploration,alliance,special',
            'points' => 'required|integer|min:1|max:1000',
            'requirements' => 'required|json',
            'is_hidden' => 'boolean',
            'is_repeatable' => 'boolean',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate file upload data
     */
    protected function validateFileUploadData(array $data)
    {
        $rules = [
            'file' => 'required|file|max:5120',  // 5MB max
            'type' => 'required|in:avatar,screenshot,document',
            'description' => 'nullable|string|max:255',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate search parameters
     */
    protected function validateSearchParameters(array $data)
    {
        $rules = [
            'query' => 'nullable|string|max:255',
            'filters' => 'nullable|array',
            'sort_by' => 'nullable|string|max:50',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate pagination parameters
     */
    protected function validatePaginationParameters(array $data)
    {
        $rules = [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate date range
     */
    protected function validateDateRange(array $data)
    {
        $rules = [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate numeric range
     */
    protected function validateNumericRange(array $data, string $field, int $min = 0, int $max = 999999)
    {
        $rules = [
            "{$field}_min" => "nullable|integer|min:{$min}|max:{$max}",
            "{$field}_max" => "nullable|integer|min:{$min}|max:{$max}|gte:{$field}_min",
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate postal code
     */
    protected function validatePostalCode($postalCode, $country = null)
    {
        $rules = [
            'postal_code' => ['required', new Postalcode($country)],
        ];

        return $this->validateGameData(['postal_code' => $postalCode], $rules);
    }

    /**
     * Validate IBAN number
     */
    protected function validateIbanNumber($iban)
    {
        $rules = [
            'iban' => ['required', new Iban()],
        ];

        return $this->validateGameData(['iban' => $iban], $rules);
    }

    /**
     * Validate BIC code
     */
    protected function validateBicCode($bic)
    {
        $rules = [
            'bic' => ['required', new Bic()],
        ];

        return $this->validateGameData(['bic' => $bic], $rules);
    }

    /**
     * Validate credit card number
     */
    protected function validateCreditCard($creditCard)
    {
        $rules = [
            'credit_card' => ['required', new CreditCard()],
        ];

        return $this->validateGameData(['credit_card' => $creditCard], $rules);
    }

    /**
     * Validate ISBN
     */
    protected function validateIsbn($isbn)
    {
        $rules = [
            'isbn' => ['required', new Isbn()],
        ];

        return $this->validateGameData(['isbn' => $isbn], $rules);
    }

    /**
     * Validate EAN
     */
    protected function validateEan($ean)
    {
        $rules = [
            'ean' => ['required', new Ean()],
        ];

        return $this->validateGameData(['ean' => $ean], $rules);
    }

    /**
     * Validate ULID
     */
    protected function validateUlid($ulid)
    {
        $rules = [
            'ulid' => ['required', new Ulid()],
        ];

        return $this->validateGameData(['ulid' => $ulid], $rules);
    }

    /**
     * Validate JWT token
     */
    protected function validateJwtToken($jwt)
    {
        $rules = [
            'jwt' => ['required', new Jwt()],
        ];

        return $this->validateGameData(['jwt' => $jwt], $rules);
    }

    /**
     * Validate Base64 data
     */
    protected function validateBase64Data($base64)
    {
        $rules = [
            'base64' => ['required', new Base64()],
        ];

        return $this->validateGameData(['base64' => $base64], $rules);
    }

    /**
     * Validate Data URI
     */
    protected function validateDataUri($dataUri)
    {
        $rules = [
            'data_uri' => ['required', new DataUri()],
        ];

        return $this->validateGameData(['data_uri' => $dataUri], $rules);
    }

    /**
     * Validate HSV color value
     */
    protected function validateHsvColor($hsv)
    {
        $rules = [
            'hsv' => ['required', new Hsv()],
        ];

        return $this->validateGameData(['hsv' => $hsv], $rules);
    }

    /**
     * Validate business data with comprehensive validation
     */
    protected function validateBusinessData(array $data)
    {
        $rules = [
            'business_name' => ['required', 'string', 'max:255', new Clean()],
            'business_type' => 'required|in:sole_proprietorship,partnership,corporation,llc',
            'tax_number' => ['nullable', 'string', 'max:50', new Clean()],
            'registration_number' => ['nullable', 'string', 'max:50', new Clean()],
            'business_address' => ['nullable', 'string', 'max:500', new Clean()],
            'business_city' => ['nullable', 'string', 'max:100', new Clean()],
            'business_country' => ['required', 'string', 'size:2'],
            'business_phone' => 'nullable|string|max:20',
            'business_email' => 'nullable|email|max:255',
            'business_website' => 'nullable|url|max:255',
            'business_description' => ['nullable', 'string', 'max:1000', new Clean()],
            'bank_iban' => 'nullable|string|max:34',
            'bank_bic' => 'nullable|string|max:11',
            'product_isbn' => 'nullable|string|max:17',
            'product_ean' => 'nullable|string|max:18',
        ];

        // Add conditional validation
        if (! empty($data['bank_iban'])) {
            $rules['bank_iban'][] = new Iban();
        }
        if (! empty($data['bank_bic'])) {
            $rules['bank_bic'][] = new Bic();
        }
        if (! empty($data['product_isbn'])) {
            $rules['product_isbn'][] = new Isbn();
        }
        if (! empty($data['product_ean'])) {
            $rules['product_ean'][] = new Ean();
        }

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate technical data with comprehensive validation
     */
    protected function validateTechnicalData(array $data)
    {
        $rules = [
            'api_token' => ['nullable', 'string', 'max:255', new Clean()],
            'webhook_url' => 'nullable|url|max:255',
            'integration_key' => 'nullable|string|max:100',
            'jwt_secret' => 'nullable|string|max:500',
            'base64_encoded_data' => 'nullable|string|max:10000',
            'data_uri' => 'nullable|string|max:10000',
            'tech_description' => ['nullable', 'string', 'max:1000', new Clean()],
            'preferred_language' => 'required|string|in:en,es,fr,de,it,pt,ru,ja,ko,zh',
            'timezone' => 'required|string|max:50',
        ];

        // Add conditional validation
        if (! empty($data['jwt_secret'])) {
            $rules['jwt_secret'][] = new Jwt();
        }
        if (! empty($data['base64_encoded_data'])) {
            $rules['base64_encoded_data'][] = new Base64();
        }
        if (! empty($data['data_uri'])) {
            $rules['data_uri'][] = new DataUri();
        }
        if (! empty($data['integration_key'])) {
            $rules['integration_key'][] = new Ulid();
        }

        return $this->validateGameData($data, $rules);
    }
}
