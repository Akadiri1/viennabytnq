import os
import sys
from PIL import Image

def compress_images(input_dir, output_dir=None, quality=60, max_width=800):
    """
    Compresses and resizes all JPEG and PNG images in a directory.
    
    Args:
        input_dir (str): Path to the directory containing input images.
        output_dir (str, optional): Path to output directory. Defaults to 'compressed' inside input_dir.
        quality (int, optional): Output image quality (for JPEGs). 1-100, defaults to 60.
        max_width (int, optional): Maximum width to resize to, maintaining aspect ratio. Defaults to 800.
    """
    if not os.path.exists(input_dir):
        print(f"Error: Directory '{input_dir}' not found.")
        sys.exit(1)

    if output_dir is None:
        output_dir = os.path.join(input_dir, "compressed")
    
    os.makedirs(output_dir, exist_ok=True)
    
    supported_formats = ('.jpg', '.jpeg', '.png')
    processed_count = 0

    print(f"Scanning directory: {input_dir}")
    print(f"Target quality: {quality}, Max width: {max_width}px")

    for filename in os.listdir(input_dir):
        if filename.lower().endswith(supported_formats):
            filepath = os.path.join(input_dir, filename)
            output_filepath = os.path.join(output_dir, filename)

            try:
                with Image.open(filepath) as img:
                    # Convert to RGB if necessary (e.g., for PNG to JPG conversion or RGBA)
                    if img.mode in ("RGBA", "P"):
                        img = img.convert("RGB")
                    
                    # Resize if wider than max_width
                    if img.width > max_width:
                        ratio = max_width / float(img.width)
                        new_height = int(float(img.height) * float(ratio))
                        img = img.resize((max_width, new_height), Image.Resampling.LANCZOS)
                        print(f"Resizing {filename} to {max_width}x{new_height}...")
                    
                    # Ensure output is JPEG for best compression on web
                    if output_filepath.lower().endswith('.png'):
                        output_filepath = output_filepath[:-4] + '.jpg'

                    # Save with reduced quality and structural optimization
                    img.save(output_filepath, "JPEG", optimize=True, quality=quality)
                    
                    original_size = os.path.getsize(filepath) / 1024
                    new_size = os.path.getsize(output_filepath) / 1024
                    reduction = ((original_size - new_size) / original_size) * 100
                    
                    print(f"Compressed '{filename}' -> {new_size:.1f}KB (-{reduction:.1f}%)")
                    processed_count += 1
                    
            except Exception as e:
                print(f"Failed to process '{filename}': {e}")
                
    print(f"\nDone! Processed {processed_count} images.")
    print(f"Optimized images saved to: {output_dir}")

if __name__ == "__main__":
    print("-" * 40)
    print("Vienna eCommerce Image Compressor")
    print("-" * 40)
    
    # Pre-defined target directories
    base_dir = os.path.dirname(os.path.abspath(__file__))
    target_dirs = [
        os.path.join(base_dir, "www", "images"),
        os.path.join(base_dir, "uploads")
    ]
    
    quality = 60
    max_width = 800
    
    for target_dir in target_dirs:
        if os.path.exists(target_dir):
            print(f"\nProcessing Directory: {target_dir}")
            compress_images(target_dir, quality=quality, max_width=max_width)
        else:
            print(f"\nSkipping missing directory: {target_dir}")

