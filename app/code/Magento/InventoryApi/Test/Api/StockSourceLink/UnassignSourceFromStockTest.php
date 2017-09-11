<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryApi\Test\Api\StockSourceLink;

use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\TestFramework\TestCase\WebapiAbstract;

class UnassignSourceFromStockTest extends WebapiAbstract
{
    /**#@+
     * Service constants
     */
    const RESOURCE_PATH_GET_ASSIGNED_SOURCES_FOR_STOCK = '/V1/inventory/stock/get-assigned-sources';
    const SERVICE_NAME_GET_ASSIGNED_SOURCES_FOR_STOCK = 'inventoryApiGetAssignedSourcesForStockV1';
    const RESOURCE_PATH_UNASSIGN_SOURCES_FROM_STOCK = '/V1/inventory/stock/unassign-source';
    const SERVICE_NAME_UNASSIGN_SOURCES_FROM_STOCK = 'inventoryApiUnassignSourceFromStockV1';
    /**#@-*/

    /**
     * Preconditions:
     * Sources to Stock links:
     *   Source-1 - Stock-1
     *   Source-2 - Stock-1
     *
     * Test case:
     *   Unassign Source-1 from Stock-1
     *
     * Expected data:
     *  Only Source-2 is assigned on Stock-1
     *
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_link.php
     */
    public function testUnassignSourceFromStock()
    {
        $sourceId = 1;
        $stockId = 1;
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH_UNASSIGN_SOURCES_FROM_STOCK . '/' . $stockId . '/' . $sourceId,
                'httpMethod' => Request::HTTP_METHOD_DELETE,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME_UNASSIGN_SOURCES_FROM_STOCK,
                'operation' => self::SERVICE_NAME_UNASSIGN_SOURCES_FROM_STOCK . 'Execute',
            ],
        ];
        (TESTS_WEB_API_ADAPTER == self::ADAPTER_REST)
            ? $this->_webApiCall($serviceInfo)
            : $this->_webApiCall($serviceInfo, ['sourceId' => $sourceId, 'stockId' => $stockId]);

        $assignedSourcesForStock = $this->getAssignedSourcesForStock($stockId);
        self::assertEquals([2], array_column($assignedSourcesForStock, SourceInterface::SOURCE_ID));
    }

    /**
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock.php
     * @param string|int $sourceId
     * @param string|int $stockId
     * @param array $expectedErrorData
     * @throws \Exception
     * @dataProvider dataProviderWrongParameters
     */
    public function testUnassignSourceFromStockWithWrongParameters($sourceId, $stockId, array $expectedErrorData)
    {
        if (TESTS_WEB_API_ADAPTER == self::ADAPTER_SOAP) {
            $this->markTestSkipped(
                'Test works only for REST adapter because in SOAP one source_id/stock_id would be converted'
                . ' into zero (zero is allowed input for service ner mind it\'s illigible value as'
                . ' there are no Sources(Stocks) in the system with source_id/stock_id given)'
            );
        }
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH_UNASSIGN_SOURCES_FROM_STOCK . '/' . $stockId . '/' . $sourceId,
                'httpMethod' => Request::HTTP_METHOD_DELETE,
            ],
        ];
        try {
            $this->_webApiCall($serviceInfo);
            $this->fail('Expected throwing exception');
        } catch (\Exception $e) {
            $errorData = $this->processRestExceptionResult($e);
            self::assertEquals($expectedErrorData['rest_message'], $errorData['message']);
            self::assertEquals(Exception::HTTP_BAD_REQUEST, $e->getCode());
        }
    }

    /**
     * @return array
     */
    public function dataProviderWrongParameters()
    {
        return [
            'not_numeric_stock_id' => [
                1,
                'not_numeric',
                [
                    'message' => 'Invalid type for value: "not_numeric". Expected Type: "int".',
                ],
            ],
            'not_numeric_source_id' => [
                'not_numeric',
                1,
                [
                    'message' => 'Invalid type for value: "not_numeric". Expected Type: "int".',
                ],
            ],
        ];
    }

    /**
     * @param int $stockId
     * @return array
     */
    private function getAssignedSourcesForStock(int $stockId)
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH_GET_ASSIGNED_SOURCES_FOR_STOCK . '/' . $stockId,
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME_GET_ASSIGNED_SOURCES_FOR_STOCK,
                'operation' => self::SERVICE_NAME_GET_ASSIGNED_SOURCES_FOR_STOCK . 'Execute',
            ],
        ];
        $response = (TESTS_WEB_API_ADAPTER == self::ADAPTER_REST)
            ? $this->_webApiCall($serviceInfo)
            : $this->_webApiCall($serviceInfo, ['stockId' => $stockId]);
        return $response;
    }
}