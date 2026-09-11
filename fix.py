file_path = "app/Jobs/ApplyBulkPricingRuleJob.php"
with open(file_path, "r") as f:
    content = f.read()

find_str = "$modesToUpdate = $this->rule->mode_type ? [$this->rule->mode_type] : ['online', 'home_visit', 'clinic_visit'];"

replace_str = """
                $normalizedMode = null;
                if ($this->rule->mode_type) {
                    $normalizedMode = match (strtolower(trim($this->rule->mode_type))) {
                        'online' => 'online',
                        'home visit' => 'home_visit',
                        'clinic' => 'clinic_visit',
                        default => strtolower(str_replace(' ', '_', trim($this->rule->mode_type)))
                    };
                }
                $modesToUpdate = $normalizedMode ? [$normalizedMode] : ['online', 'home_visit', 'clinic_visit'];
"""

# Replace all occurrences (both applyToDoctorsPayout and applyToWebsiteDoctor)
content = content.replace(find_str, replace_str)

with open(file_path, "w") as f:
    f.write(content)

print("Done")
