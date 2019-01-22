# psps

Middleware for unify 3rd party APIs requests

## General Description

Basic data is API's vocabulary. 

<u>/configs/forms</u> are schemas to client's `<form>` that mostly used by  [our form generator](https://github.com/gobemarketing/paymentform). This <u>process</u> could involve several <u>instance</u>s (API). Assoc data (values with known keys) is sending to this middleware that checks and prepares it: map keys, key-values, recalculate values if needed and applies to them defaults and overrides if set. In the same way handles API response to provide unification. 

One request-response called <u>operation</u>. API might require several requests-responses to produce one <u>transaction</u> - terms similar to SQL. <u>Transaction</u> has no sense if one of <u>operation</u>s failed.

<u>Process</u> requested by client should be succeeded. For this reason it consists of several <u>transaction</u>s that are possibilities. So <u>process</u> is successful on first successful <u>transaction</u> and failed if all <u>transaction</u>s are failed.

