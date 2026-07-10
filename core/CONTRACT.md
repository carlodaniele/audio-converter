# Core Contract Notes

The core must accept a normalized request and produce a normalized response.

Required principles:

1. deterministic behavior for the same input when possible
2. explicit quality flags
3. adapter-agnostic error model
4. no framework-specific dependencies

Adapters are responsible for mapping platform payloads to this core contract.
