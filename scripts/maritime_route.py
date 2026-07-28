import json
import sys

import searoute as sr


def main():
    payload = json.load(sys.stdin)
    origin = payload["origin"]
    destination = payload["destination"]
    route = sr.searoute([origin["longitude"], origin["latitude"]], [destination["longitude"], destination["latitude"]])
    print(json.dumps(route["geometry"]["coordinates"]))


if __name__ == "__main__":
    main()
