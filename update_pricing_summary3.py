file_path = "resources/views/doctor_carelix/partials/services_pricing_summary.blade.php"
with open(file_path, "r") as f:
    content = f.read()

td_find = """                                        <td>
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
                                        </td>"""

td_replace = """                                        <td>
                                            @if($tempRule && ($isActive || $isFuture))
                                                <strong class="{{ $isActive ? 'text-success' : 'text-danger' }}">{{ $fmtPrice($prices['doctor']) }}</strong>
                                                <div class="mt-1 text-muted" style="font-size: 0.7rem; line-height: 1.2;">
                                                    From: {{ $start->format('d M Y, h:i A') }}<br>
                                                    To: {{ $end ? $end->format('d M Y, h:i A') : 'Ongoing' }}
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>"""

content = content.replace(td_find, td_replace)

with open(file_path, "w") as f:
    f.write(content)

print("Done")
