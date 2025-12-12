<?php

namespace App\Services;

use Aws\Textract\TextractClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Storage;

class TextractService
{
    protected $client;

    public function __construct()
    {
        $config = config('services.textract');
        
        $this->client = new TextractClient([
            'version' => 'latest',
            'region' => $config['region'],
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
        ]);
    }

    /**
     * Extract table data from an image using AWS Textract
     *
     * @param string $imagePath Path to the image file
     * @return array Extracted table data
     */
    public function extractTableFromImage($imagePath)
    {
        try {
            // Read the image file
            $imageContent = file_get_contents($imagePath);
            $imageBase64 = base64_encode($imageContent);

            // Call Textract AnalyzeDocument API with Tables feature
            $result = $this->client->analyzeDocument([
                'Document' => [
                    'Bytes' => base64_decode($imageBase64),
                ],
                'FeatureTypes' => ['TABLES'],
            ]);

            // Parse the response to extract table data
            return $this->parseTableData($result);
        } catch (AwsException $e) {
            throw new \Exception('Failed to extract data from image: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Parse Textract response to extract structured table data
     *
     * @param array $result Textract API response
     * @return array Parsed table rows
     */
    protected function parseTableData($result)
    {
        $blocks = $result['Blocks'] ?? [];
        $tables = [];
        $cells = [];
        $words = [];
        $relationships = [];

        // First pass: collect all blocks and build word map
        foreach ($blocks as $block) {
            if ($block['BlockType'] === 'TABLE') {
                $tables[] = $block;
            } elseif ($block['BlockType'] === 'CELL') {
                $cells[$block['Id']] = $block;
            } elseif ($block['BlockType'] === 'WORD') {
                $words[$block['Id']] = $block['Text'] ?? '';
            }
        }

        // Build relationships map
        foreach ($blocks as $block) {
            if (isset($block['Relationships'])) {
                foreach ($block['Relationships'] as $relationship) {
                    if ($relationship['Type'] === 'CHILD') {
                        $relationships[$block['Id']] = $relationship['Ids'] ?? [];
                    }
                }
            }
        }

        $parsedRows = [];

        // Process each table
        foreach ($tables as $table) {
            $tableCells = [];
            $tableRelationships = $relationships[$table['Id']] ?? [];

            // Get all cells in this table
            foreach ($tableRelationships as $cellId) {
                if (isset($cells[$cellId])) {
                    $cell = $cells[$cellId];
                    $rowIndex = $cell['RowIndex'] ?? 0;
                    $columnIndex = $cell['ColumnIndex'] ?? 0;
                    
                    // Extract text from cell using word map
                    $cellText = '';
                    $cellRelationships = $relationships[$cellId] ?? [];
                    $cellWords = [];
                    
                    foreach ($cellRelationships as $wordId) {
                        if (isset($words[$wordId])) {
                            $cellWords[] = $words[$wordId];
                        }
                    }
                    
                    $cellText = implode(' ', $cellWords);
                    
                    // Only store non-empty cells
                    if (!empty(trim($cellText))) {
                        $tableCells[$rowIndex][$columnIndex] = trim($cellText);
                    }
                }
            }

            // Convert to array of rows
            if (!empty($tableCells)) {
                ksort($tableCells);
                $maxRow = max(array_keys($tableCells));
                $maxCol = 0;
                
                // Find max column index
                foreach ($tableCells as $row) {
                    if (!empty($row)) {
                        $maxCol = max($maxCol, max(array_keys($row)));
                    }
                }
                
                // Build complete rows with empty cells where needed
                for ($rowIdx = 1; $rowIdx <= $maxRow; $rowIdx++) {
                    $row = [];
                    for ($colIdx = 1; $colIdx <= $maxCol; $colIdx++) {
                        $row[] = $tableCells[$rowIdx][$colIdx] ?? '';
                    }
                    // Only add row if it has at least one non-empty cell
                    if (!empty(array_filter($row))) {
                        $parsedRows[] = $row;
                    }
                }
            }
        }

        return $parsedRows;
    }

    /**
     * Extract text from image (fallback method)
     *
     * @param string $imagePath Path to the image file
     * @return string Extracted text
     */
    public function extractTextFromImage($imagePath)
    {
        try {
            $imageContent = file_get_contents($imagePath);
            $imageBase64 = base64_encode($imageContent);

            $result = $this->client->detectDocumentText([
                'Document' => [
                    'Bytes' => base64_decode($imageBase64),
                ],
            ]);

            $text = '';
            foreach ($result['Blocks'] as $block) {
                if ($block['BlockType'] === 'LINE') {
                    $text .= ($block['Text'] ?? '') . "\n";
                }
            }

            return trim($text);
        } catch (AwsException $e) {
            throw new \Exception('Failed to extract text from image: ' . $e->getMessage());
        }
    }
}

