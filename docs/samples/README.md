# Sample documents for extraction testing

Drop real (or realistically messy) title scans here to test the extraction
pipeline against something other than the synthetic fixture in
tests/Fixtures/salvage-title.png.

A phone photo is the most useful input — that is what customers actually
upload, and it is where a cheaper model is most likely to break.

## These files are deliberately NOT committed

A real title carries the owner's name, address and VIN. Everything in this
directory except this README is gitignored, because anything committed to git
history is permanent and this is customer PII.

If a sample must be shared, redact the owner block first; the VIN, vehicle and
odometer fields are enough to exercise the extractor.
