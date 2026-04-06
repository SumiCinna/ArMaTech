from flask import Flask, jsonify, request
from flask_cors import CORS

app = Flask(__name__)
# Enable CORS so your PHP front-end can request data from this API
CORS(app)

# ==========================================
# 1. THE KNOWLEDGE BASE (Dictionary)
# ==========================================
# This holds all the options for your auto-complete fields.
# It only stores fields that vary by category (Value Modifiers).
DEVICE_DATABASE = {
    "Smartphone": {
        "brands": ["Apple", "Samsung", "Xiaomi", "Oppo", "Vivo", "Realme", "Huawei", "OnePlus"],
        "ram": ["4GB", "6GB", "8GB", "12GB", "16GB"],
        "storage": ["64GB", "128GB", "256GB", "512GB", "1TB"],
        "os": ["iOS", "Android", "HarmonyOS"]
    },
    "Laptop": {
        "brands": ["Dell", "Lenovo", "HP", "Apple", "Asus", "Acer", "MSI", "Razer"],
        "ram": ["4GB", "8GB", "16GB", "32GB", "64GB"],
        "storage": ["256GB SSD", "512GB SSD", "1TB SSD", "2TB SSD", "500GB HDD", "1TB HDD"],
        "processor": ["Intel Core i3", "Intel Core i5", "Intel Core i7", "Intel Core i9", "AMD Ryzen 3", "AMD Ryzen 5", "AMD Ryzen 7", "AMD Ryzen 9", "Apple M1", "Apple M2", "Apple M3"],
        "os": ["Windows 10", "Windows 11", "macOS", "Linux"]
    },
    "Tablet": {
        "brands": ["Apple", "Samsung", "Huawei", "Lenovo", "Xiaomi", "Amazon"],
        "ram": ["3GB", "4GB", "6GB", "8GB", "16GB"],
        "storage": ["32GB", "64GB", "128GB", "256GB", "512GB", "1TB"],
        "sim_support": ["Wi-Fi Only", "Wi-Fi + Cellular (LTE/5G)"]
    },
    "Smartwatch": {
        "brands": ["Apple", "Samsung", "Huawei", "Garmin", "Fitbit", "Amazfit", "Xiaomi"],
        "case_size": ["38mm", "40mm", "41mm", "42mm", "44mm", "45mm", "49mm"],
        "connectivity": ["GPS / Wi-Fi", "GPS + Cellular (LTE)"]
    },
    "Gaming Console": {
        "brands": ["Sony", "Microsoft", "Nintendo", "Valve", "ASUS"],
        "models": ["PlayStation 5 Disc", "PlayStation 5 Digital", "PlayStation 4 Pro", "Xbox Series X", "Xbox Series S", "Nintendo Switch OLED", "Nintendo Switch V2", "Steam Deck", "ROG Ally"],
        "storage": ["256GB", "512GB", "825GB", "1TB"],
        "controllers": ["Unit Only", "1 Controller", "2 Controllers", "3 Controllers", "4 Controllers"]
    },
    "Camera": {
        "brands": ["Canon", "Nikon", "Sony", "Fujifilm", "Panasonic", "Olympus"],
        "camera_type": ["Mirrorless", "DSLR", "Compact / Point & Shoot", "Action Camera"],
        "sensor_type": ["Full Frame", "APS-C", "Micro Four Thirds", "Medium Format"]
    }
}

# ==========================================
# 2. GENERIC WORKFLOW DATA (Universal)
# ==========================================
# As requested, these apply to ALL devices during the pawn process.
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
    app.run(debug=True, port=5000)