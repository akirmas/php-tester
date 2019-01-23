# psps

Middleware for unify 3rd party APIs requests

[TOC]

## General Description, Terms and Definitions

Basic data is API's <u>vocabulary</u>. 

<u>/configs/forms</u> are schemas to client's `<form>` that mostly used by  [our form generator](https://github.com/gobemarketing/paymentform). This <u>process</u> could involve several <u>instance</u>s (API). Assoc data (values with known keys) is sending to this middleware that checks and prepares it: map keys, key-values, recalculate values if needed and applies to them defaults and overrides if set. In the same way handles API response to provide unification. 

One request-response called <u>operation</u>. API might require several requests-responses to produce one <u>transaction</u> - terms similar to SQL. <u>Transaction</u> has no sense if one of <u>operation</u>s failed.

<u>Process</u> requested by client should be succeeded. For this reason it consists of several <u>transaction</u>s that are possibilities. So <u>process</u> is successful on first successful <u>transaction</u> and failed if all <u>transaction</u>s are failed.

## Details

### Phases

| #    | Direction | Phase  | Description                                       | Key vocabulary |
| ---- | --------- | ------ | ------------------------------------------------- | -------------- |
| 0    | Request   | Raw    | Data is client's input                            | Unified        |
| 1    | Request   | Filled | Data is filled with config defines                | Unified        |
| 2    | Request   | Calced | Added additional portion from script calculations | Unified        |
| 3    | Request   | Formed | Data is translated for API                        | API's          |
| 4    | Response  | Raw    |                                                   | API's          |
| 5    | Response  | Formed |                                                   | Unified        |
| 6    | Response  | Filled |                                                   | Unified        |

### `quizUrl` processing

Mid is sync, Exchange is async

| Client         |      | Mid            |      | Exchange |      | API       |      | User | Description                          |
| -------------- | ---- | -------------- | ---- | -------- | ---- | --------- | ---- | ---- | ------------------------------------ |
| req0           | >    |                |      |          |      |           |      |      |                                      |
|                | >    | req0           | >    |          |      |           |      |      |                                      |
|                |      |                |      |          | >    | req0      |      |      |                                      |
|                |      |                |      |          | <    | req0 + qU |      |      |                                      |
|                | <    | req0 + qU + aU | <    |          |      |           |      |      |                                      |
| req0 + qU + aU | <    |                |      |          |      |           |      |      | Client receives quiz and answer URLs |
| answer@aU      | >    |                |      |          |      |           |      |      | Client awaits answer                 |
|                |      |                | >    | ...      |      |           |      |      | Yet no answer                        |
|                |      |                |      |          |      |           | ?    | qU   | quiz URL delivered to User           |
|                |      |                |      |          |      |           | <    | qD   | User fills  data                     |
|                |      |                |      |          | <    | qD        | <    |      | API receives forwards data           |
|                |      | qD             | <    |          |      |           |      |      | to Mid                               |
|                |      |                |      | qD       | <    |           |      |      | and Excange                          |
|                |      |                | <    | qD       |      |           |      |      | Exchange has what to answer          |
| answer = qD    | <    |                |      |          |      |           |      |      | Client receives data                 |

