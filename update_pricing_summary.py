file_path = "resources/views/doctor_carelix/partials/services_pricing_summary.blade.php"
with open(file_path, "r") as f:
    content = f.read()

# 1. Add activeTempRules at the top PHP block
php_find = """
    $requestStatusLabel = static function (string $status): string {
"""

php_replace = """
    $activeTempRules = \App\Models\BulkPricingRule::where('status', 1)
        ->where('time_period', 'temporary')
        ->where('pricing_type', 'doctor_payout')
        ->get();
        
    $tier1Cities = \App\Models\Location::where('tier', 1)->pluck('name')->toArray();
    
    $normalizeMode = static function($mode) {
        if (!$mode) return null;
        return match (strtolower(trim($mode))) {
            'online' => 'online',
            'home visit' => 'home_visit',
            'clinic' => 'clinic_visit',
            default => strtolower(str_replace(' ', '_', trim($mode)))
        };
    };

    $getMatchingTempRule = static function($subId, $modeKey) use ($activeTempRules, $serviceId, $locationName, $normalizeMode, $tier1Cities) {
        foreach ($activeTempRules as $rule) {
            if ($rule->service_id && (int)$rule->service_id !== (int)$serviceId) continue;
            if ($rule->sub_service_id && (int)$rule->sub_service_id !== (int)$subId) continue;
            
            $ruleMode = $normalizeMode($rule->mode_type);
            if ($ruleMode && $ruleMode !== $modeKey) continue;
            
            if ($rule->city_filter && $rule->city_filter !== 'all') {
                if ($rule->city_filter === 'tier_1') {
                    if (!in_array($locationName, $tier1Cities)) continue;
                } else {
                    if (strtolower($locationName) !== strtolower($rule->city_filter)) continue;
                }
            }
            
            if ($rule->time_period_start_date) {
                return $rule;
            }
        }
        return null;
    };

    $requestStatusLabel = static function (string $status): string {
"""

# 2. Render the badge under "Your charge"
td_find = """
                                        <td><strong>{{ $fmtPrice($prices['doctor']) }}</strong></td>
"""

td_replace = """
                                        @php
                                            $tempRule = $getMatchingTempRule($subId, $modeKey);
                                        @endphp
                                        <td>
                                            <strong>{{ $fmtPrice($prices['doctor']) }}</strong>
                                            @if($tempRule)
                                                @php
                                                    $now = \Carbon\Carbon::now();
                                                    $start = \Carbon\Carbon::parse($tempRule->time_period_start_date);
                                                    $end = $tempRule->time_period_end_date ? \Carbon\Carbon::parse($tempRule->time_period_end_date) : null;
                                                    
                                                    $isActive = $now->gte($start) && (!$end || $now->lte($end));
                                                    $isFuture = $now->lt($start);
                                                @endphp
                                                @if($isActive || $isFuture)
                                                    <div class="mt-1">
                                                        <span class="dr-portal-req-badge" style="background: {{ $isActive ? '#dcfce7' : '#fee2e2' }}; color: {{ $isActive ? '#166534' : '#991b1b' }};">
                                                            @if($isActive)
                                                                Active till: {{ $end ? $end->format('d M Y') : 'Ongoing' }}
                                                            @else
                                                                Starts: {{ $start->format('d M Y') }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endif
                                            @endif
                                        </td>
"""

content = content.replace(php_find, php_replace)
content = content.replace(td_find, td_replace)

with open(file_path, "w") as f:
    f.write(content)

print("Done")
