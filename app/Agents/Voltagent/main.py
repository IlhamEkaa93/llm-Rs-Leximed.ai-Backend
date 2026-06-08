import sys
import argparse
import json

def main():
    parser = argparse.ArgumentParser(description="Voltagent Downstream Semantics Text Formatter Engine.")
    parser.add_argument('--gateway_json', type=str, required=True, help='Sanitized JSON payload from OpenClaw Gateway')
    
    args = parser.parse_args()
    
    try:
        # Membaca data kiriman hasil filtrasi dari OpenClaw Gateway
        data = json.loads(args.gateway_json)
        text_to_process = data.get("passed_text", "")
        prompt_instruction = data.get("passed_prompt", "")
        
        # Di sini tempat Voltagent melakukan rekayasa promt akhir/merapikan kata medis
        # Output dicetak mentah agar langsung disimpan ke kolom ai_summary PostgreSQL
        print(text_to_process)
        
    except Exception as e:
        print(f"Voltagent Parsing Crash Node: {str(e)}")

if __name__ == "__main__":
    main()
