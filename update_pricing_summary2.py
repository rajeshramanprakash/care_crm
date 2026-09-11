file_path = "resources/views/doctor_carelix/partials/services_pricing_summary.blade.php"
with open(file_path, "r") as f:
    content = f.read()

# 1. Update the table header
th_find = """
                                    <th>Consultation mode</th>
                                    <th>Your charge</th>
                                    <th>Request new price (₹)</th>
"""

th_replace = """
                                    <th>Consultation mode</th>
                                    <th>Your charge</th>
                                    <th>Temporary price</th>
                                    <th>Request new price (₹)</th>
"""

# 2. Update the table cell
td_find = """
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

td_replace = """
                                        @php
                                            $tempRule = $getMatchingTempRule($subId, $modeKey);
                                            $now = \Carbon\Carbon::now();
                                            $isActive = false;
                                            $isFuture = false;
                                            $start = null;
                                            $end = null;
                                            if ($tempRule) {
                                                $start = \Carbon\Carbon::parse($tempRule->time_period_start_date);
                                                $end = $tempRule->time_period_end_date ? \Carbon\Carbon::parse($tempRule->time_period_end_date) : null;
                                                $isActive = $now->gte($start) && (!$end || $now->lte($end));
                                                $isFuture = $now->lt($start);
                                            }
                                        @endphp
                                        <td><strong>{{ $fmtPrice($prices['doctor']) }}</strong></td>
                                        <td>
                                            @if($tempRule && ($isActive || $isFuture))
                                                <strong>{{ $fmtPrice($prices['doctor']) }}</strong>
                                                <div class="mt-1">
                                                    <span class="dr-portal-req-badge" style="background: {{ $isActive ? '#dcfce7' : '#fee2e2' }}; color: {{ $isActive ? '#166534' : '#991b1b' }};">
                                                        @if($isActive)
                                                            Active till: {{ $end ? $end->format('d M Y') : 'Ongoing' }}
                                                        @else
                                                            Starts: {{ $start->format('d M Y') }}
                                                        @endif
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
"""

content = content.replace(th_find, th_replace)
content = content.replace(td_find, td_replace)

with open(file_path, "w") as f:
    f.write(content)

print("Done")
