#!/usr/bin/env python
# Reflects the requests from HTTP methods GET, POST, PUT, and DELETE
# Check the gist: https://gist.github.com/ssi-anik/5caf23edf6b9d5f4da170dc9a36182bb

import json
from http.server import HTTPServer, BaseHTTPRequestHandler
from optparse import OptionParser

port = 9669


class RequestHandler(BaseHTTPRequestHandler):

    def log_message(self, format, *args):
        pass

    def process_request(self):
        request_path = self.path
        method = self.command.upper()

        request_headers = self.headers
        content_length = request_headers.get('Content-Length')
        length = int(content_length) if content_length else 0

        data = {
            'path': request_path,
            'method': method,
            'content-length': content_length,
            'headers': dict(request_headers),
            'data': json.loads(str(self.rfile.read(length).decode('ascii')) or '{}')
        }
        print(json.dumps(data, indent=2))

        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.end_headers()
        self.wfile.write(bytes(json.dumps({
            'error': False,
            'message': 'Handled ' + method + ' request',
            'received': data,
        }), 'utf-8'))

    def serve_get(self):
        request_path = self.path

        if request_path == '/favicon.ico':
            # Don't serve favicon
            self.send_response(200)
            self.end_headers()
            return

        self.process_request()

    def serve_post(self):
        self.process_request()

    def serve_options(self):
        self.process_request()

    do_GET = serve_get
    do_HEAD = serve_get
    do_DELETE = serve_post
    do_POST = serve_post
    do_PUT = serve_post
    do_PATCH = serve_post
    do_OPTIONS = serve_options


def main():
    print('Listening on 0.0.0.0:%s' % (port))
    server = HTTPServer(('', port), RequestHandler)
    server.serve_forever()


if __name__ == "__main__":
    parser = OptionParser()
    parser.usage = "Creates an http-server that will echo out HEAD, GET, POST, PUT, PATCH, DELETE in JSON format"
    parser.add_option('-p', '--port', default=9669)
    (options, args) = parser.parse_args()

    port = int(options.port)

    main()

# Commands to run
# python server.py --port 9669