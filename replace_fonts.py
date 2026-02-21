import os
import re

views_dir = r"c:\wamp64\www\viennabyTNQ\v1\views"

google_fonts_regex = re.compile(r'<link[^>]*href=["\']https://fonts\.googleapis\.com/css2\?family=Cormorant\+Garamond[^"\']*["\'][^>]*>')
new_google_fonts_link = '<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">'

google_fonts_regex_2 = re.compile(r'<link[^>]*href=["\']https://fonts\.googleapis\.com/css2\?family=Inter[^"\']*Cormorant\+Garamond[^"\']*["\'][^>]*>')

for root, dirs, files in os.walk(views_dir):
    for file in files:
        if file.endswith('.php'):
            filepath = os.path.join(root, file)
            try:
                with open(filepath, 'r', encoding='utf-8') as f:
                    content = f.read()

                original_content = content
                
                # Replace Google Fonts links
                content = google_fonts_regex.sub(new_google_fonts_link, content)
                content = google_fonts_regex_2.sub(new_google_fonts_link, content)
                
                # Replace Inter with Lato in tailwind config and css
                content = content.replace("['Inter'", "['Lato'")
                content = content.replace('["Inter"', '["Lato"')
                
                # Replace Cormorant Garamond with Playfair Display in tailwind config and css
                content = content.replace("['Cormorant Garamond'", "['Playfair Display'")
                content = content.replace('["Cormorant Garamond"', '["Playfair Display"')
                
                # Replace in inline CSS
                content = content.replace("'Cormorant Garamond'", "'Playfair Display'")
                content = content.replace('"Cormorant Garamond"', '"Playfair Display"')
                content = content.replace("'Inter'", "'Lato'")
                content = content.replace('"Inter"', '"Lato"')

                if content != original_content:
                    with open(filepath, 'w', encoding='utf-8') as f:
                        f.write(content)
                    print(f"Updated {filepath}")
            except Exception as e:
                print(f"Error accessing {filepath}: {e}")
