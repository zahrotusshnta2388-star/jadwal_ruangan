import os

# folder tempat script ini berada
script_dir = os.path.dirname(os.path.abspath(__file__))

# Tentukan folder induk sebagai folder root yang akan di-scan
root_folder = os.path.abspath(os.path.join(script_dir, os.pardir))

# daftar folder yang mau dikecualikan (folder-folder Laravel umum)
exclude_dirs = {
    ".next", "node_modules", ".env", ".env.local", ".git", 
    ".gitignore", "vendor", "storage", "bootstrap/cache",
    "public/storage", ".idea", ".vscode", "__pycache__"
}

def print_tree(startpath, prefix="", depth=0, max_depth=4):
    """
    Mencetak struktur folder dengan batasan kedalaman
    """
    if depth > max_depth:
        return
    
    try:
        items = os.listdir(startpath)
    except PermissionError:
        return
    
    items.sort()
    # Filter exclude dan hidden files (yang dimulai dengan .)
    items = [i for i in items if i not in exclude_dirs and not i.startswith('.')]
    
    for index, item in enumerate(items):
        path = os.path.join(startpath, item)
        connector = "└── " if index == len(items) - 1 else "├── "
        print(prefix + connector + item)
        
        if os.path.isdir(path):
            extension = "    " if index == len(items) - 1 else "│   "
            print_tree(path, prefix + extension, depth + 1, max_depth)

if __name__ == "__main__":
    # Cetak nama folder induk
    print(f"📁 {os.path.basename(root_folder)}/")
    print("=" * 50)
    
    # Mulai pemindaian dari folder induk
    print_tree(root_folder)
    
    print("\n" + "=" * 50)
    print(f"📍 Path: {root_folder}")