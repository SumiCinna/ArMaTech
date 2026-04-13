from flask import Flask, jsonify, request
from flask_cors import CORS

app = Flask(__name__)
# Enable CORS so your PHP front-end can request data from this API
CORS(app)

# ==========================================
# 1. THE KNOWLEDGE BASE (Dictionary)
# ==========================================
# Updated for 2026 Tech Market
DEVICE_DATABASE = {
    "Smartphone": {
        "brands": ["Apple", "Samsung", "Xiaomi", "Oppo", "Vivo", "Realme", "Huawei", "OnePlus", "Google", "Motorola", "Honor"],
        "models": [
            "iPhone 17 Pro Max", "iPhone 17 Pro", "iPhone 17", "iPhone 17 Slim", 
            "iPhone 16 Pro Max", "iPhone 16 Pro", "iPhone 16", "iPhone SE (4th Gen)",
            "Galaxy S26 Ultra", "Galaxy S26+", "Galaxy S26", 
            "Galaxy S25 Ultra", "Galaxy S25+", "Galaxy S25 FE",
            "Galaxy Z Fold 7", "Galaxy Z Flip 7", "Galaxy Z Fold 6", "Galaxy Z Flip 6",
            "Pixel 10 Pro XL", "Pixel 10 Pro", "Pixel 10", "Pixel 9 Pro Fold",
            "Xiaomi 15 Pro", "Xiaomi 15 Ultra", "OnePlus 14", "OnePlus 13", "ROG Phone 9"
        ],
        "ram": ["4GB", "6GB", "8GB", "12GB", "16GB", "24GB"],
        "storage": ["64GB", "128GB", "256GB", "512GB", "1TB"],
        "os": ["iOS", "Android", "HarmonyOS"]
    },
    "Laptop": {
        "brands": ["Dell", "Lenovo", "HP", "Apple", "Asus", "Acer", "MSI", "Razer", "Microsoft"],
        "models": [
            "MacBook Pro 16-inch (M4 Max)", "MacBook Pro 14-inch (M4 Pro)", "MacBook Air 15-inch (M4)", 
            "Dell XPS 16", "Dell XPS 14", "Dell XPS 13", 
            "Lenovo ThinkPad X1 Carbon Gen 14", "Lenovo Yoga Pro 9i", 
            "ASUS ROG Zephyrus G16", "ASUS ROG Zephyrus G14", "ASUS Zenbook 14 OLED",
            "HP Spectre x360 14", "HP Omen Transcend 14",
            "Razer Blade 16", "Razer Blade 14", "Microsoft Surface Laptop 7"
        ],
        "ram": ["8GB", "16GB", "18GB", "24GB", "32GB", "36GB", "64GB", "96GB", "128GB"],
        "storage": ["256GB SSD", "512GB SSD", "1TB SSD", "2TB SSD", "4TB SSD"],
        "processor": [
            "Apple M4 Max", "Apple M4 Pro", "Apple M4", "Apple M3 Max", 
            "Intel Core Ultra 9 (Series 2)", "Intel Core Ultra 7 (Series 2)", "Intel Core Ultra 5",
            "AMD Ryzen AI 9 HX 370", "AMD Ryzen 9 9000 Series", "AMD Ryzen 7 8000 Series",
            "Snapdragon X Elite", "Snapdragon X Plus"
        ],
        "os": ["Windows 11", "macOS", "ChromeOS", "Linux"]
    },
    "Tablet": {
        "brands": ["Apple", "Samsung", "Huawei", "Lenovo", "Xiaomi", "Amazon", "OnePlus", "Google"],
        "models": [
            "iPad Pro 13-inch (M4)", "iPad Pro 11-inch (M4)", 
            "iPad Air 13-inch (M2)", "iPad Air 11-inch (M2)", 
            "iPad mini (A17 Pro)", "iPad (11th Gen)",
            "Galaxy Tab S10 Ultra", "Galaxy Tab S10+", "Galaxy Tab S10", 
            "Galaxy Tab S9 FE", "Pixel Tablet 2", "OnePlus Pad 2"
        ],
        "ram": ["4GB", "6GB", "8GB", "12GB", "16GB"],
        "storage": ["64GB", "128GB", "256GB", "512GB", "1TB", "2TB"],
        "sim_support": ["Wi-Fi Only", "Wi-Fi + Cellular (5G/LTE)"]
    },
    "Smartwatch": {
        "brands": ["Apple", "Samsung", "Garmin", "Huawei", "Fitbit", "Amazfit", "Google", "Coros"],
        "models": [
            "Apple Watch Series 11", "Apple Watch Series 10", 
            "Apple Watch Ultra 3", "Apple Watch Ultra 2", "Apple Watch SE 3",
            "Galaxy Watch 8 Ultra", "Galaxy Watch 8", "Galaxy Watch 7", 
            "Pixel Watch 4", "Pixel Watch 3", 
            "Garmin Fenix 8", "Garmin Epix Pro (Gen 2)", "Garmin Venu 3"
        ],
        "case_size": ["38mm", "40mm", "41mm", "42mm", "44mm", "45mm", "46mm", "47mm", "49mm"],
        "connectivity": ["GPS Only / Wi-Fi", "GPS + Cellular (LTE)"]
    },
    "Gaming Console": {
        "brands": ["Sony", "Microsoft", "Nintendo", "Valve", "ASUS", "Lenovo"],
        "models": [
            "PlayStation 5 Pro", "PlayStation 5 Slim (Disc)", "PlayStation 5 Slim (Digital)", 
            "Nintendo Switch 2", "Nintendo Switch OLED", "Nintendo Switch Lite",
            "Xbox Series X (2TB)", "Xbox Series X (1TB)", "Xbox Series S (1TB)", 
            "Steam Deck OLED", "ASUS ROG Ally X", "Lenovo Legion Go", "MSI Claw"
        ],
        "storage": ["64GB", "256GB", "512GB", "825GB", "1TB", "2TB"],
        "controllers": ["Unit Only", "1 Controller", "2 Controllers", "3 Controllers", "4 Controllers"]
    },
    "Camera": {
        "brands": ["Canon", "Nikon", "Sony", "Fujifilm", "Panasonic", "Olympus", "Leica", "DJI", "GoPro"],
        "models": [
            "Sony A9 III", "Sony A7R V", "Sony A7 IV", "Sony FX3",
            "Canon EOS R1", "Canon EOS R5 Mark II", "Canon EOS R6 Mark II", 
            "Nikon Z9", "Nikon Z8", "Nikon Zf", "Nikon Z6 III",
            "Fujifilm X100VI", "Fujifilm X-T5", "Fujifilm X-T50", "Fujifilm GFX100 II",
            "Panasonic Lumix G9 II", "Panasonic Lumix S5 II",
            "DJI Osmo Action 5 Pro", "GoPro HERO 13 Black"
        ],
        "camera_type": ["Mirrorless", "DSLR", "Compact / Point & Shoot", "Action Camera", "Cinema Camera"],
        "sensor_type": ["Full Frame", "APS-C", "Micro Four Thirds", "Medium Format", "1-inch"]
    }
}

# ==========================================
# 2. GENERIC WORKFLOW DATA (Universal)
# ==========================================
UNIVERSAL_WORKFLOW = {
    "photographic_evidence": ["Front View", "Back/Sides View", "Serial/IMEI Verification"],
    "inspection_interview": [
        "Cloud/Google/iCloud Account Removed?",
        "Device Passcode/Lock Screen Removed?",
        "Original Box Included?",
        "Original Charger Included?",
        "Store Receipt Provided?"
    ]
}

# ==========================================
# 3. API ENDPOINTS
# ==========================================

@app.route('/api/categories', methods=['GET'])
def get_categories():
    """Returns a list of all supported device categories."""
    categories = list(DEVICE_DATABASE.keys())
    return jsonify({"status": "success", "categories": categories})

@app.route('/api/schema/<category>', methods=['GET'])
def get_schema(category):
    """
    Returns the specific auto-complete fields for a chosen category, 
    plus the universal workflow steps.
    """
    if category not in DEVICE_DATABASE:
        return jsonify({"status": "error", "message": "Category not found"}), 404
    
    return jsonify({
        "status": "success",
        "category": category,
        "dynamic_specs": DEVICE_DATABASE[category],
        "universal_workflow": UNIVERSAL_WORKFLOW
    })

@app.route('/api/autocomplete', methods=['GET'])
def autocomplete():
    """
    Search endpoint for dynamic typing (e.g., User types "Sam", returns "Samsung").
    Usage: /api/autocomplete?category=Smartphone&field=brands&q=Sam
    """
    category = request.args.get('category')
    field = request.args.get('field')
    query = request.args.get('q', '').lower()

    if category not in DEVICE_DATABASE or field not in DEVICE_DATABASE[category]:
        return jsonify({"status": "error", "message": "Invalid category or field"}), 400

    options = DEVICE_DATABASE[category][field]
    
    # If there's a search query, filter the options
    if query:
        filtered_options = [opt for opt in options if query in opt.lower()]
    else:
        filtered_options = options

    return jsonify({"status": "success", "results": filtered_options})

if __name__ == '__main__':
    # Runs the API on port 5000
    app.run(host='0.0.0.0', port=int(os.environ.get('PORT', 5000)))
