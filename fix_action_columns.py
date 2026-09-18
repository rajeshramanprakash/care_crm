import os
import re

directory = 'resources/views/admin'

for root, dirs, files in os.walk(directory):
    for file in files:
        if file == 'index.blade.php':
            filepath = os.path.join(root, file)
            with open(filepath, 'r') as f:
                content = f.read()

            # We need to find the Action column definition in JS.
            # Usually it starts with { data: 'action' } or { data: null } 
            # and ends with },
            
            # Let's search for the block
            # This regex looks for { data: 'action' ... } or { data: null ... } containing @can
            # but wait, it's easier to find the render function that returns the actions
            
            # A pattern to find the JS object that renders actions
            pattern = re.compile(r'\{\s*(data:\s*(\'action\'|null)[^}]*?render:\s*function\s*\([^)]*\)\s*\{.*?\})\s*\}', re.DOTALL)
            
            matches = list(pattern.finditer(content))
            if not matches:
                continue
                
            new_content = content
            for match in matches:
                full_block = match.group(0)
                inner_content = match.group(1)
                
                # Check if it already has visible:
                if 'visible:' in full_block:
                    continue
                
                # Extract all @can conditions
                cans = re.findall(r'@can\(\'([^\']+)\'\)', full_block)
                if not cans:
                    continue
                
                cans = list(set(cans)) # unique
                
                # Build the condition
                condition_parts = [f"auth()->user()->can('{c}')" for c in cans]
                condition_str = " || ".join(condition_parts)
                
                visible_prop = f"visible: {{{{ ({condition_str}) ? 'true' : 'false' }}}},"
                
                # Insert visible_prop after data: ...
                # Let's just insert it right after the opening brace
                new_block = re.sub(r'^\{', f"{{\n                        {visible_prop}", full_block)
                
                new_content = new_content.replace(full_block, new_block)
            
            if new_content != content:
                with open(filepath, 'w') as f:
                    f.write(new_content)
                print(f"Updated {filepath}")

