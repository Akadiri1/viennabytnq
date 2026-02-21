const fs = require('fs');
const path = require('path');

const viewsDir = 'c:\\wamp64\\www\\viennabyTNQ\\v1\\views';

const googleFontsRegex = /<link[^>]*href=["']https:\/\/fonts\.googleapis\.com\/css2\?family=Cormorant\+Garamond[^"']*["'][^>]*>/g;
const newGoogleFontsLink = '<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">';

const googleFontsRegex2 = /<link[^>]*href=["']https:\/\/fonts\.googleapis\.com\/css2\?family=Inter[^"']*Cormorant\+Garamond[^"']*["'][^>]*>/g;

function processDirectory(directory) {
    const files = fs.readdirSync(directory);
    for (const file of files) {
        const fullPath = path.join(directory, file);
        if (fs.statSync(fullPath).isDirectory()) {
            processDirectory(fullPath);
        } else if (file.endsWith('.php')) {
            let content = fs.readFileSync(fullPath, 'utf8');
            const originalContent = content;

            content = content.replace(googleFontsRegex, newGoogleFontsLink);
            content = content.replace(googleFontsRegex2, newGoogleFontsLink);

            content = content.replace(/\['Inter'/g, "['Lato'");
            content = content.replace(/\["Inter"/g, '["Lato"');

            content = content.replace(/\['Cormorant Garamond'/g, "['Playfair Display'");
            content = content.replace(/\["Cormorant Garamond"/g, '["Playfair Display"');

            content = content.replace(/'Cormorant Garamond'/g, "['Playfair Display'");
            content = content.replace(/"Cormorant Garamond"/g, '["Playfair Display"');
            content = content.replace(/'Inter'/g, "'Lato'");
            content = content.replace(/"Inter"/g, '"Lato"');

            if (content !== originalContent) {
                fs.writeFileSync(fullPath, content, 'utf8');
                console.log(`Updated ${fullPath}`);
            }
        }
    }
}

try {
    processDirectory(viewsDir);
    console.log("Done.");
} catch (e) {
    console.error(e);
}
