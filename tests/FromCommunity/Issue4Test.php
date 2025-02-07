<?php

declare(strict_types=1);

namespace OpenAPIValidationTests\FromCommunity;

use GuzzleHttp\Psr7\ServerRequest;
use OpenAPIValidation\PSR7\ValidatorBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @see https://github.com/lezhnev74/openapi-psr7-validator/issues/4
 */
final class Issue4Test extends TestCase
{
    public function testItResolvesSchemaRefsFromYamlStringGreen() : void
    {
        $yamlFile  = __DIR__ . '/../stubs/SchemaWithRefs.yaml';
        $validator = (new ValidatorBuilder())->fromYamlFile($yamlFile)->getServiceRequestValidator();

        $validator->validate($this->makeRequest());
        $this->addToAssertionCount(1);
    }

    public function testItResolvesSchemaRefsFromYamlFileGreen() : void
    {
        $yamlFile  = __DIR__ . '/../stubs/SchemaWithRefs.yaml';
        $validator = (new ValidatorBuilder())->fromYamlFile($yamlFile)->getServiceRequestValidator();

        $validator->validate($this->makeRequest());
        $this->addToAssertionCount(1);
    }

    protected function makeRequest() : ServerRequest
    {
        return new ServerRequest(
            'POST',
            'http://localhost:8000/products.create',
            ['Content-Type' => 'application/json'],
            <<<JSON
{
    "test": {
        "input": "some data"
    }
}
JSON
        );
    }
}
