export 'url_opener_stub.dart'
    if (dart.library.js_interop) 'url_opener_web.dart';

import 'url_opener_stub.dart'
    if (dart.library.js_interop) 'url_opener_web.dart';

class UrlOpener {
  static bool open(String url) => openExternalUrl(url);
}


