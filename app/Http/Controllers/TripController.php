<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Driver;
use App\Models\Vessel;
use App\Services\TextractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Trip::with(['driver', 'vessel']);

        if ($request->has('driver') && $request->driver) {
            $query->whereHas('driver', function ($q) use ($request) {
                $q->where('name', $request->driver);
            });
        }

        if ($request->has('vessel') && $request->vessel) {
            $query->whereHas('vessel', function ($q) use ($request) {
                $q->where('name', $request->vessel);
            });
        }

        if ($request->has('date') && $request->date) {
            $query->whereDate('trip_date', $request->date);
        }

        $trips = $query->latest('created_at')
            ->get();

        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();

        return view('trips.index', compact('trips', 'drivers', 'vessels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();
        return view('trips.create', compact('drivers', 'vessels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'crew_name' => ['required', 'string', 'max:255'],
            'driver_id' => ['required', 'exists:drivers,id'],
            'vessel_id' => ['required', 'exists:vessels,id'],
            'trip_date' => ['required', 'date'],
            'pick_up_time' => ['required'],
            'from_location' => ['required', 'string', 'max:255'],
            'to_location' => ['required', 'string', 'max:255'],
            'crew_phone' => ['nullable', 'string', 'max:255'],
            'crew_address' => ['nullable', 'string'],
        ]);

        // Set default status to assigned
        $validated['status'] = Trip::STATUS_ASSIGNED;

        Trip::create($validated);

        return redirect()->route('trips.index')->with('success', 'Trip created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Trip $trip)
    {
        $trip->load(['driver', 'vessel', 'activityLogs.user', 'activityLogs.driver']);
        return view('trips.show', compact('trip'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Trip $trip)
    {
        $drivers = Driver::orderBy('name')->get();
        $vessels = Vessel::orderBy('name')->get();
        return view('trips.edit', compact('trip', 'drivers', 'vessels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'crew_name' => ['required', 'string', 'max:255'],
            'driver_id' => ['required', 'exists:drivers,id'],
            'vessel_id' => ['required', 'exists:vessels,id'],
            'trip_date' => ['required', 'date'],
            'pick_up_time' => ['required'],
            'from_location' => ['required', 'string', 'max:255'],
            'to_location' => ['required', 'string', 'max:255'],
            'crew_phone' => ['nullable', 'string', 'max:255'],
            'crew_address' => ['nullable', 'string'],
        ]);

        // Keep existing status, don't update it through the form
        $trip->update($validated);

        return redirect()->route('trips.index')->with('success', 'Trip updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trip $trip)
    {
        $trip->delete();

        return redirect()->route('trips.index')->with('success', 'Trip deleted successfully!');
    }

    /**
     * Extract trips from uploaded image using AWS Textract
     */
    public function extractFromImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:10240'], // 10MB max
            'trip_date' => ['nullable', 'date'], // Optional: if not provided, try to extract from image
        ]);

        try {
            // Store the uploaded image temporarily
            $uploadedFile = $request->file('image');
            $imagePath = $uploadedFile->store('temp', 'local');
            
            // Get the full absolute path using Storage
            $fullPath = Storage::disk('local')->path($imagePath);

            // Verify file exists
            if (!file_exists($fullPath)) {
                throw new \Exception('Uploaded file could not be saved. Please try again.');
            }

            // Initialize Textract service
            $textractService = new TextractService();
            
            // Extract table data from image
            $tableRows = $textractService->extractTableFromImage($fullPath);

            // Clean up temporary file
            Storage::disk('local')->delete($imagePath);

            if (empty($tableRows)) {
                return redirect()->route('trips.index')
                    ->with('error', 'No table data found in the image. Please ensure the image contains a clear table.');
            }

            // Parse and create trips from extracted data
            $created = $this->createTripsFromTableData($tableRows, $request->trip_date);

            if ($created['success'] > 0) {
                $message = "Successfully created {$created['success']} trip(s) from the image.";
                
                // Add info about auto-created drivers and vessels
                $additionalInfo = [];
                if ($created['drivers_created'] > 0) {
                    $additionalInfo[] = "{$created['drivers_created']} driver(s) auto-created";
                }
                if ($created['vessels_created'] > 0) {
                    $additionalInfo[] = "{$created['vessels_created']} vessel(s) auto-created";
                }
                if (!empty($additionalInfo)) {
                    $message .= " (" . implode(', ', $additionalInfo) . ")";
                }
                
                if ($created['failed'] > 0) {
                    $message .= " {$created['failed']} trip(s) could not be created.";
                }
                return redirect()->route('trips.index')->with('success', $message);
            } else {
                return redirect()->route('trips.index')
                    ->with('error', 'No trips could be created. Please check the image format and ensure it contains valid table data.');
            }
        } catch (\Exception $e) {
            return redirect()->route('trips.index')
                ->with('error', 'Failed to extract data from image: ' . $e->getMessage());
        }
    }

    /**
     * Create trips from extracted table data
     */
    protected function createTripsFromTableData(array $tableRows, $defaultDate = null)
    {
        $success = 0;
        $failed = 0;
        $driversCreated = 0;
        $vesselsCreated = 0;
        $tripDate = $defaultDate ? Carbon::parse($defaultDate) : Carbon::today();

        if (empty($tableRows)) {
            return [
                'success' => 0,
                'failed' => 0,
                'drivers_created' => 0,
                'vessels_created' => 0,
            ];
        }

        // Try to identify header row and date
        $headerRowIndex = 0;
        $dataStartIndex = 1;
        
        // Check first row for date pattern (might be header with date)
        if (!empty($tableRows[0])) {
            $firstRowText = implode(' ', array_map('trim', $tableRows[0]));
            // Try to parse date from first row (e.g., "Monday, 18 August 2025")
            if (preg_match('/(\d{1,2})\s+(\w+)\s+(\d{4})/', $firstRowText, $matches)) {
                try {
                    $parsedDate = Carbon::createFromFormat('d F Y', $matches[1] . ' ' . $matches[2] . ' ' . $matches[3]);
                    $tripDate = $parsedDate;
                    $headerRowIndex = 0;
                    $dataStartIndex = 1;
                } catch (\Exception $e) {
                    // Keep default date if parsing fails
                }
            }
        }

        // Process data rows (skip header)
        for ($i = $dataStartIndex; $i < count($tableRows); $i++) {
            $row = $tableRows[$i];
            
            // Skip if row is too short or empty
            if (count($row) < 3) {
                $failed++;
                continue;
            }

            // Expected columns: CREW NAME, DRIVER NAME, VESSEL NAME, PICK-UP TIME, FROM, TO, FOLLOW UP
            // Handle cases where columns might be in different positions
            $crewName = trim($row[0] ?? '');
            $driverName = trim($row[1] ?? '');
            $vesselName = trim($row[2] ?? '');
            $pickUpTime = trim($row[3] ?? '');
            $fromLocation = trim($row[4] ?? '');
            $toLocation = trim($row[5] ?? '');
            $followUp = trim($row[6] ?? '');

            // Skip header rows - check if row contains header-like text
            if ($this->isHeaderRow($row)) {
                continue; // Skip header row, don't count as failed
            }

            // Skip empty rows (check essential fields)
            if (empty($crewName) && empty($driverName) && empty($vesselName)) {
                $failed++;
                continue;
            }

            // Skip if essential fields are missing
            if (empty($crewName) || empty($driverName) || empty($vesselName)) {
                $failed++;
                continue;
            }

            // Find or create driver by name (case-insensitive, exact match first, then partial)
            $driver = Driver::whereRaw('LOWER(name) = ?', [strtolower($driverName)])->first();
            
            // If exact match not found, try partial match
            if (!$driver) {
                $driver = Driver::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($driverName) . '%'])->first();
            }
            
            // If still not found, create a new driver (default to internal type)
            if (!$driver) {
                try {
                    $driver = Driver::create([
                        'name' => $driverName,
                        'type' => Driver::TYPE_INTERNAL, // Default to internal for auto-created drivers
                    ]);
                    $driversCreated++;
                    Log::info('Auto-created driver: ' . $driverName);
                } catch (\Exception $e) {
                    Log::error('Failed to create driver: ' . $e->getMessage(), ['driver_name' => $driverName]);
                    $failed++;
                    continue;
                }
            }

            // Find or create vessel by name (case-insensitive, exact match first, then partial)
            $vessel = Vessel::whereRaw('LOWER(name) = ?', [strtolower($vesselName)])->first();
            
            // If exact match not found, try partial match
            if (!$vessel) {
                $vessel = Vessel::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($vesselName) . '%'])->first();
            }
            
            // If still not found, create a new vessel
            if (!$vessel) {
                try {
                    $vessel = Vessel::create([
                        'name' => $vesselName,
                    ]);
                    $vesselsCreated++;
                    Log::info('Auto-created vessel: ' . $vesselName);
                } catch (\Exception $e) {
                    Log::error('Failed to create vessel: ' . $e->getMessage(), ['vessel_name' => $vesselName]);
                    $failed++;
                    continue;
                }
            }

            // Parse pick-up time (e.g., "0300PM" -> "15:00")
            $parsedTime = $this->parsePickUpTime($pickUpTime);

            // Create trip
            try {
                Trip::create([
                    'crew_name' => $crewName,
                    'driver_id' => $driver->id,
                    'vessel_id' => $vessel->id,
                    'trip_date' => $tripDate->format('Y-m-d'),
                    'pick_up_time' => $parsedTime,
                    'from_location' => $fromLocation ?: 'N/A',
                    'to_location' => $toLocation ?: 'N/A',
                    'status' => Trip::STATUS_ASSIGNED,
                ]);
                $success++;
            } catch (\Exception $e) {
                Log::error('Failed to create trip from extracted data: ' . $e->getMessage(), [
                    'row' => $row,
                    'crew_name' => $crewName,
                    'driver_name' => $driverName,
                    'vessel_name' => $vesselName,
                ]);
                $failed++;
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'drivers_created' => $driversCreated,
            'vessels_created' => $vesselsCreated,
        ];
    }

    /**
     * Check if a row is a header row by looking for common header patterns
     *
     * @param array $row
     * @return bool
     */
    protected function isHeaderRow(array $row): bool
    {
        // Common header patterns (case-insensitive)
        $headerPatterns = [
            'crew name',
            'driver name',
            'vessel name',
            'pick-up time',
            'pick up time',
            'pickup time',
            'from',
            'to',
            'follow up',
            'followup',
            'confirm action',
            'confirm actioin', // Handle typo in image
            'action',
        ];

        // Check if row contains multiple header-like terms
        $rowText = implode(' ', array_map('strtolower', array_map('trim', $row)));
        $matches = 0;

        foreach ($headerPatterns as $pattern) {
            if (stripos($rowText, $pattern) !== false) {
                $matches++;
            }
        }

        // If 3 or more header patterns match, it's likely a header row
        if ($matches >= 3) {
            return true;
        }

        // Also check if first few columns match header patterns exactly
        if (count($row) >= 3) {
            $firstCol = strtolower(trim($row[0] ?? ''));
            $secondCol = strtolower(trim($row[1] ?? ''));
            $thirdCol = strtolower(trim($row[2] ?? ''));

            $headerColumns = [
                'crew name' => ['crew name'],
                'driver name' => ['driver name'],
                'vessel name' => ['vessel name'],
            ];

            // Check if first 3 columns match header column names
            if (
                (stripos($firstCol, 'crew') !== false || stripos($firstCol, 'name') !== false) &&
                (stripos($secondCol, 'driver') !== false || stripos($secondCol, 'name') !== false) &&
                (stripos($thirdCol, 'vessel') !== false || stripos($thirdCol, 'name') !== false)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse pick-up time from various formats
     * Examples: "0300PM", "3:00 PM", "15:00"
     */
    protected function parsePickUpTime($timeString)
    {
        $timeString = trim($timeString);
        
        // Format: "0300PM" or "0300 PM"
        if (preg_match('/(\d{1,2})(\d{2})(AM|PM)/i', $timeString, $matches)) {
            $hour = (int)$matches[1];
            $minute = (int)$matches[2];
            $meridian = strtoupper($matches[3]);
            
            if ($meridian === 'PM' && $hour < 12) {
                $hour += 12;
            } elseif ($meridian === 'AM' && $hour === 12) {
                $hour = 0;
            }
            
            return sprintf('%02d:%02d', $hour, $minute);
        }
        
        // Format: "3:00 PM" or "15:00"
        try {
            $carbon = Carbon::createFromFormat('g:i A', $timeString);
            return $carbon->format('H:i');
        } catch (\Exception $e) {
            try {
                $carbon = Carbon::createFromFormat('H:i', $timeString);
                return $carbon->format('H:i');
            } catch (\Exception $e2) {
                // Default to current time if parsing fails
                return Carbon::now()->format('H:i');
            }
        }
    }
}
