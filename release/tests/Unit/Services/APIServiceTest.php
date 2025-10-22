<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Api\ApiInfoService;
use App\Services\Api\ResponseBuilderService;
use App\Services\Api\RequestParameterService;
use App\Services\Api\PaginationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Tests\TestCase;

class APIServiceTest extends TestCase
{
    public function test_api_info_service_version_and_urls(): void
    {
        $service = new ApiInfoService();

        $this->assertSame('2.0', $service->getApiVersion());
        $this->assertTrue($service->checkApiVersion());

        $this->assertStringEndsWith('/api/v2/documentation', $service->getApiDocumentationUrl());
        $this->assertStringEndsWith('/api/v2/changelog', $service->getApiChangelogUrl());
        $this->assertStringEndsWith('/api/v2/migration-guide', $service->getApiMigrationGuideUrl());

        $notices = $service->getApiDeprecationNotices();
        $this->assertArrayHasKey('v1_endpoint', $notices);
        $this->assertArrayHasKey('migration_guide', $notices);
        $this->assertStringEndsWith('/api/v2/migration-guide', $notices['migration_guide']);
    }

    public function test_response_builder_success_and_error(): void
    {
        $builder = new ResponseBuilderService();

        $success = $builder->successResponse(['id' => 1, 'name' => 'ok'], 'Success', 200, ['meta' => 'x']);
        $this->assertSame(200, $success->status());
        $payload = $success->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertSame('Success', $payload['message']);
        $this->assertSame('2.0', $payload['version']);
        $this->assertArrayHasKey('timestamp', $payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('meta', $payload);

        $error = $builder->errorResponse('Error', ['bad' => 'thing'], 422, ['ctx' => 'y']);
        $this->assertSame(422, $error->status());
        $errPayload = $error->getData(true);
        $this->assertFalse($errPayload['success']);
        $this->assertSame('Error', $errPayload['message']);
        $this->assertSame('2.0', $errPayload['version']);
        $this->assertArrayHasKey('errors', $errPayload);
        $this->assertArrayHasKey('meta', $errPayload);
    }

    public function test_paginated_response_structure_with_collection(): void
    {
        $builder = new ResponseBuilderService();
        $data = new Collection([['id' => 1], ['id' => 2]]);
        $resp = $builder->paginatedResponse($data, 'Success');
        $this->assertSame(200, $resp->status());
        $payload = $resp->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertArrayHasKey('pagination', $payload);
        $this->assertArrayHasKey('version', $payload);
        $this->assertSame('2.0', $payload['version']);
    }

    public function test_request_parameter_service_parsing(): void
    {
        $paramsService = new RequestParameterService();

        $request = Request::create('/api/v2/items', 'GET', [
            'include' => 'user,orders',
            'fields' => 'id,name',
            'sort_by' => 'name',
            'sort_order' => 'asc',
        ]);

        $include = $paramsService->getIncludeParams($request);
        $fields = $paramsService->getFieldsParams($request);
        $sorting = $paramsService->getSortingParams($request);
        $rate = $paramsService->getRateLimitInfo();

        $this->assertArrayHasKey('user', $include);
        $this->assertArrayHasKey('orders', $include);
        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertSame('name', $sorting['sort_by']);
        $this->assertSame('asc', $sorting['sort_order']);
        $this->assertSame('2.0', $rate['version']);
    }

    public function test_pagination_service_defaults_for_non_paginator(): void
    {
        $service = new PaginationService();
        $pagination = $service->getPaginationData([['id' => 1], ['id' => 2]]);

        $this->assertArrayHasKey('current_page', $pagination);
        $this->assertArrayHasKey('links', $pagination);
        $this->assertNull($pagination['links']['first']);
        $this->assertNull($pagination['links']['last']);
        $this->assertNull($pagination['links']['prev']);
        $this->assertNull($pagination['links']['next']);
    }
}
