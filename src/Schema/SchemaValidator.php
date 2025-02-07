<?php

declare(strict_types=1);

namespace OpenAPIValidation\Schema;

use cebe\openapi\spec\Schema as CebeSchema;
use OpenAPIValidation\Schema\Exception\SchemaMismatch;
use OpenAPIValidation\Schema\Keywords\AllOf;
use OpenAPIValidation\Schema\Keywords\AnyOf;
use OpenAPIValidation\Schema\Keywords\Enum;
use OpenAPIValidation\Schema\Keywords\Items;
use OpenAPIValidation\Schema\Keywords\Maximum;
use OpenAPIValidation\Schema\Keywords\MaxItems;
use OpenAPIValidation\Schema\Keywords\MaxLength;
use OpenAPIValidation\Schema\Keywords\MaxProperties;
use OpenAPIValidation\Schema\Keywords\Minimum;
use OpenAPIValidation\Schema\Keywords\MinItems;
use OpenAPIValidation\Schema\Keywords\MinLength;
use OpenAPIValidation\Schema\Keywords\MinProperties;
use OpenAPIValidation\Schema\Keywords\MultipleOf;
use OpenAPIValidation\Schema\Keywords\Not;
use OpenAPIValidation\Schema\Keywords\Nullable;
use OpenAPIValidation\Schema\Keywords\OneOf;
use OpenAPIValidation\Schema\Keywords\Pattern;
use OpenAPIValidation\Schema\Keywords\Properties;
use OpenAPIValidation\Schema\Keywords\Required;
use OpenAPIValidation\Schema\Keywords\Type;
use OpenAPIValidation\Schema\Keywords\UniqueItems;
use function count;

// This will load a whole schema and data to validate if one matches another
final class SchemaValidator implements Validator
{
    // How to treat the data (affects writeOnly/readOnly keywords)
    public const VALIDATE_AS_REQUEST  = 0;
    public const VALIDATE_AS_RESPONSE = 1;

    /** @var int strategy of validation - Request or Response (affected by writeOnly/readOnly keywords) */
    private $validationStrategy;

    public function __construct(int $validationStrategy = self::VALIDATE_AS_RESPONSE)
    {
        \Respect\Validation\Validator::in([self::VALIDATE_AS_REQUEST, self::VALIDATE_AS_RESPONSE])->assert($validationStrategy);

        $this->validationStrategy = $validationStrategy;
    }

    /** {@inheritdoc} */
    public function validate($data, CebeSchema $schema, ?BreadCrumb $breadCrumb = null) : void
    {
        $breadCrumb = $breadCrumb ?? new BreadCrumb();

        try {
            // These keywords are not part of the JSON Schema at all (new to OAS)
            (new Nullable($schema))->validate($data, $schema->nullable);

            // We don't want to validate any more if the value is a valid Null
            if ($data === null) {
                return;
            }

            // This keywords come directly from JSON Schema Validation, they are the same as in JSON schema
            // https://tools.ietf.org/html/draft-wright-json-schema-validation-00#section-5
            if (isset($schema->multipleOf)) {
                (new MultipleOf($schema))->validate($data, $schema->multipleOf);
            }

            if (isset($schema->maximum)) {
                $exclusiveMaximum = (bool) ($schema->exclusiveMaximum ?? false);
                (new Maximum($schema))->validate($data, $schema->maximum, $exclusiveMaximum);
            }

            if (isset($schema->minimum)) {
                $exclusiveMinimum = (bool) ($schema->exclusiveMinimum ?? false);
                (new Minimum($schema))->validate($data, $schema->minimum, $exclusiveMinimum);
            }

            if (isset($schema->maxLength)) {
                (new MaxLength($schema))->validate($data, $schema->maxLength);
            }

            if (isset($schema->minLength)) {
                (new MinLength($schema))->validate($data, $schema->minLength);
            }

            if (isset($schema->pattern)) {
                (new Pattern($schema))->validate($data, $schema->pattern);
            }

            if (isset($schema->maxItems)) {
                (new MaxItems($schema))->validate($data, $schema->maxItems);
            }

            if (isset($schema->minItems)) {
                (new MinItems($schema))->validate($data, $schema->minItems);
            }

            if (isset($schema->uniqueItems)) {
                (new UniqueItems($schema))->validate($data, $schema->uniqueItems);
            }

            if (isset($schema->maxProperties)) {
                (new MaxProperties($schema))->validate($data, $schema->maxProperties);
            }

            if (isset($schema->minProperties)) {
                (new MinProperties($schema))->validate($data, $schema->minProperties);
            }

            if (isset($schema->required)) {
                (new Required($schema, $this->validationStrategy))->validate($data, $schema->required);
            }

            if (isset($schema->enum)) {
                (new Enum($schema))->validate($data, $schema->enum);
            }

            // The following properties are taken from the JSON Schema definition but their definitions were adjusted to the OpenAPI Specification.
            if (isset($schema->type)) {
                (new Type($schema))->validate($data, $schema->type, $schema->format);
            }

            if (isset($schema->items)) {
                (new Items($schema, $this->validationStrategy, $breadCrumb))->validate($data, $schema->items);
            }

            if (isset($schema->properties) && count($schema->properties)) {
                $additionalProperties = $schema->additionalProperties ?? null;
                (new Properties($schema, $this->validationStrategy, $breadCrumb))->validate($data, $schema->properties, $additionalProperties);
            }

            if (isset($schema->allOf) && count($schema->allOf)) {
                (new AllOf($schema, $this->validationStrategy, $breadCrumb))->validate($data, $schema->allOf);
            }

            if (isset($schema->oneOf) && count($schema->oneOf)) {
                (new OneOf($schema, $this->validationStrategy, $breadCrumb))->validate($data, $schema->oneOf);
            }

            if (isset($schema->anyOf) && count($schema->anyOf)) {
                (new AnyOf($schema, $this->validationStrategy, $breadCrumb))->validate($data, $schema->anyOf);
            }

            if (isset($schema->not)) {
                (new Not($schema, $this->validationStrategy, $breadCrumb))->validate($data, $schema->not);
            }
            //   ✓  ok, all checks are done
        } catch (SchemaMismatch $e) {
            $e->hydrateDataBreadCrumb($breadCrumb);

            throw $e;
        }
    }
}
