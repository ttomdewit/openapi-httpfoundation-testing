<?php

declare(strict_types=1);

namespace Osteel\OpenApi\Testing\Tests;

use Osteel\OpenApi\Testing\Exceptions\ValidationException;
use Osteel\OpenApi\Testing\Tests\TestCase;
use Osteel\OpenApi\Testing\Validator;
use Osteel\OpenApi\Testing\ValidatorBuilder;

class ValidatorTest extends TestCase
{
    private Validator $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = ValidatorBuilder::fromYaml(self::$yamlDefinition)->getValidator();
    }

    /*
    |--------------------------------------------------------------------------
    | Requests
    |--------------------------------------------------------------------------
    */

    public function requestTypeProvider(): array
    {
        return [
            ['httpFoundationRequest'],
            ['psr7Request'],
        ];
    }

    /**
     * @dataProvider requestTypeProvider
     */
    public function testItDoesNotValidateTheRequestWithoutPayload(string $method)
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('OpenAPI spec contains no such operation [/test,foo]');

        $request = $this->$method(static::PATH, 'delete');

        $this->sut->validate($request, static::PATH, 'foo');
    }

    /**
     * @dataProvider requestTypeProvider
     */
    public function testItValidatesTheRequestWithoutPayload(string $method)
    {
        $request = $this->$method(static::PATH, $method);
        $result  = $this->sut->validate($request, static::PATH, 'delete');

        $this->assertTrue($result);
    }

    /**
     * @dataProvider requestTypeProvider
     */
    public function testItDoesNotValidateTheRequestWithPayload(string $method)
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Body does not match schema for content-type "application/json" for Request [post /test]: Keyword validation failed: Required property \'foo\' must be present in the object Field: foo');

        $request = $this->$method(static::PATH, 'post', ['baz' => 'bar']);

        $this->sut->validate($request, static::PATH, 'post');
    }

    /**
     * @dataProvider requestTypeProvider
     */
    public function testItValidatesTheRequestWithPayload(string $method)
    {
        $request = $this->$method(static::PATH, 'post', ['foo' => 'bar']);
        $result  = $this->sut->validate($request, static::PATH, 'post');

        $this->assertTrue($result);
    }

    public function bodylessRequestMethodProvider(): array
    {
        return [
            ['delete'],
            ['get'],
            ['head'],
            ['options'],
            ['trace'],
        ];
    }

    /**
     * @dataProvider bodylessRequestMethodProvider
     */
    public function testItValidatesTheRequestWithoutPayloadUsingMethodShortcuts(string $method)
    {
        $request = $this->httpFoundationRequest(static::PATH, $method);
        $result  = $this->sut->validate($request, static::PATH, $method);

        $this->assertTrue($result);
    }

    public function requestMethodProvider(): array
    {
        return [
            ['patch'],
            ['post'],
            ['put'],
        ];
    }

    /**
     * @dataProvider requestMethodProvider
     */
    public function testItValidatesTheRequestWithPaylodUsingShortcuts(string $method)
    {
        $request = $this->httpFoundationRequest(static::PATH, $method, ['foo' => 'bar']);
        $result  = $this->sut->validate($request, static::PATH, $method);

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Responses
    |--------------------------------------------------------------------------
    */

    public function responseTypeProvider(): array
    {
        return [
            ['httpFoundationResponse'],
            ['psr7Response'],
        ];
    }

    /**
     * @dataProvider responseTypeProvider
     */
    public function testItDoesNotValidateTheResponse(string $method)
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Body does not match schema for content-type "application/json" for Response [get /test 200]: Keyword validation failed: Required property \'foo\' must be present in the object Field: foo');

        $response = $this->$method(['baz' => 'bar']);

        $this->sut->validate($response, static::PATH, 'get');
    }

    /**
     * @dataProvider responseTypeProvider
     */
    public function testItValidatesTheResponse(string $method)
    {
        $response = $this->$method(['foo' => 'bar']);
        $result   = $this->sut->validate($response, static::PATH, 'get');

        $this->assertTrue($result);
    }

    public function responseMethodProvider(): array
    {
        return [
            ['delete'],
            ['head'],
            ['options'],
            ['patch'],
            ['post'],
            ['put'],
            ['trace'],
        ];
    }

    /**
     * @dataProvider responseMethodProvider
     */
    public function testItValidatesTheResponseUsingMethodShortcuts(string $method)
    {
        $result = $this->sut->$method($this->httpFoundationResponse(), static::PATH);

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Misc
    |--------------------------------------------------------------------------
    */

    public function pathProvider(): array
    {
        return [
            ['/test'],
            ['test'],
        ];
    }

    /**
     * @dataProvider pathProvider
     */
    public function testItFixesThePath(string $path)
    {
        $response = $this->httpFoundationResponse(['foo' => 'bar']);
        $result   = $this->sut->validate($response, $path, 'get');

        $this->assertTrue($result);
    }
}
