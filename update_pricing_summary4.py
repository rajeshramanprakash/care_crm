file_path = "resources/views/doctor_carelix/partials/services_pricing_summary.blade.php"
with open(file_path, "r") as f:
    content = f.read()

td_find = """                                            @if($tempRule && ($isActive || $isFuture))
                                                <strong class="{{ $isActive ? 'text-success' : 'text-danger' }}">{{ $fmtPrice($prices['doctor']) }}</strong>"""

td_replace = """                                            @if($tempRule && ($isActive || $isFuture))
                                                @php
                                                    $displayPrice = $tempRule->pricing_type === 'website_doctor' ? $prices['website'] : $prices['doctor'];
                                                    if (!$displayPrice) {
                                                        $displayPrice = $tempRule->value; // fallback if it hasn't run yet or is 0
                                                    }
                                                @endphp
                                                <strong class="{{ $isActive ? 'text-success' : 'text-danger' }}">{{ $fmtPrice($displayPrice) }}</strong>"""

content = content.replace(td_find, td_replace)

with open(file_path, "w") as f:
    f.write(content)

print("Done")
