// Contournement d'un proxy SSL d'entreprise qui n'implémente pas la
// renégociation sécurisée (RFC 5746). Active SSL_OP_LEGACY_SERVER_CONNECT
// sur tous les contextes TLS créés par Node (npm, vite, etc.).
const tls = require('tls');
const crypto = require('crypto');

const legacyFlag = crypto.constants.SSL_OP_LEGACY_SERVER_CONNECT;
const original = tls.createSecureContext;

tls.createSecureContext = function patched(options = {}) {
    options.secureOptions = (options.secureOptions || 0) | legacyFlag;
    return original.call(this, options);
};
