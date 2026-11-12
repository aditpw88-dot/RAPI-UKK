import 'package:web/web.dart' as web;

bool openExternalUrl(String url) {
  web.window.open(url, '_blank');
  return true;
}

