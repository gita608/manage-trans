<?php

namespace App\Services;

use App\Models\PartnerRequest;
use Illuminate\Support\Facades\Log;

class PartnerRequestImageExtractionService
{
    public function __construct(
        protected TextractService $textractService,
        protected PartnerScheduleParser $parser
    ) {
    }

    /**
     * Extract partner request items from a stored schedule image.
     *
     * @return array{status: string, items: array<int, array<string, mixed>>}
     */
    public function extractFromStoredImage(string $absoluteImagePath, ?string $partnerRequestReference = null): array
    {
        try {
            $tableRows = $this->textractService->extractTableFromImage($absoluteImagePath);

            if (empty($tableRows)) {
                return [
                    'status' => PartnerRequest::EXTRACTION_FAILED,
                    'items' => [],
                ];
            }

            $items = $this->parser->parseTableRows($tableRows);

            if (empty($items)) {
                return [
                    'status' => PartnerRequest::EXTRACTION_FAILED,
                    'items' => [],
                ];
            }

            return [
                'status' => PartnerRequest::EXTRACTION_COMPLETED,
                'items' => $items,
            ];
        } catch (\Throwable $e) {
            Log::warning('Partner request image extraction failed', [
                'request_reference' => $partnerRequestReference,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => PartnerRequest::EXTRACTION_FAILED,
                'items' => [],
            ];
        }
    }
}
