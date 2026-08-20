<?php

namespace Tests\Unit;

use App\Models\Vessel;
use App\Services\PartnerScheduleParser;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerScheduleParserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('vessels', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function parseSingleRow(string $vesselName): array
    {
        $parser = new PartnerScheduleParser();

        return $parser->parseTableRows([
            ['Crew Name', 'Driver Name', 'Vessel Name', 'Pick-up Time', 'From', 'To'],
            ['Jane Doe', 'Driver B', $vesselName, '0900AM', 'Site A', 'Site B'],
        ]);
    }

    protected function createVessel(string $name): Vessel
    {
        return Vessel::withoutEvents(fn () => Vessel::create(['name' => $name]));
    }

    public function test_parser_maps_schedule_rows_to_partner_request_items(): void
    {
        $parser = new PartnerScheduleParser();

        $rows = [
            ['Crew Name', 'Driver Name', 'Vessel Name', 'Pick-up Time', 'From', 'To', 'Follow Up', 'Phone', 'Phone 2'],
            ['John Smith', 'Driver A', 'ADNOC A08', '0300PM', 'Port A', 'Airport B', 'Note', '0501111111', '0502222222'],
        ];

        $items = $parser->parseTableRows($rows, '2026-08-25');

        $this->assertCount(1, $items);
        $this->assertSame('John Smith', $items[0]['name']);
        $this->assertSame('Port A', $items[0]['from_location']);
        $this->assertSame('Airport B', $items[0]['to_location']);
        $this->assertSame('ADNOC A08', $items[0]['vessel_name_raw']);
        $this->assertNull($items[0]['driver_id']);
        $this->assertSame('15:00', $items[0]['pick_up_time']);
    }

    public function test_exact_vessel_name_matches_vessel_id(): void
    {
        $vessel = $this->createVessel('ADNOC A08');

        $items = $this->parseSingleRow('ADNOC A08');

        $this->assertSame($vessel->id, $items[0]['vessel_id']);
        $this->assertSame('ADNOC A08', $items[0]['vessel_name_raw']);
    }

    public function test_case_insensitive_exact_match_works(): void
    {
        $vessel = $this->createVessel('ADNOC A08');

        $items = $this->parseSingleRow('adnoc a08');

        $this->assertSame($vessel->id, $items[0]['vessel_id']);
        $this->assertSame('adnoc a08', $items[0]['vessel_name_raw']);
    }

    public function test_whitespace_normalization_matches_exact_vessel(): void
    {
        $vessel = $this->createVessel('ADNOC A08');

        $items = $this->parseSingleRow('  adnoc   a08 ');

        $this->assertSame($vessel->id, $items[0]['vessel_id']);
        $this->assertSame('adnoc   a08', $items[0]['vessel_name_raw']);
    }

    public function test_partial_vessel_name_does_not_auto_match(): void
    {
        $this->createVessel('ADNOC 1010');

        $items = $this->parseSingleRow('ADNOC 101');

        $this->assertNull($items[0]['vessel_id']);
        $this->assertSame('ADNOC 101', $items[0]['vessel_name_raw']);
    }

    public function test_ambiguous_duplicate_normalized_names_do_not_auto_match(): void
    {
        $this->createVessel('ADNOC A08');
        $this->createVessel('adnoc  a08');

        $items = $this->parseSingleRow('ADNOC A08');

        $this->assertNull($items[0]['vessel_id']);
        $this->assertSame('ADNOC A08', $items[0]['vessel_name_raw']);
    }

    public function test_vessel_name_raw_is_always_preserved(): void
    {
        $this->createVessel('ADNOC A08');

        $items = $this->parseSingleRow('  OCR VALUE  ');

        $this->assertNull($items[0]['vessel_id']);
        $this->assertSame('OCR VALUE', $items[0]['vessel_name_raw']);
    }

    public function test_unknown_vessel_does_not_create_vessel(): void
    {
        $countBefore = Vessel::count();

        $items = $this->parseSingleRow('UNKNOWN VESSEL');

        $this->assertSame($countBefore, Vessel::count());
        $this->assertNull($items[0]['vessel_id']);
        $this->assertSame('UNKNOWN VESSEL', $items[0]['vessel_name_raw']);
    }
}
