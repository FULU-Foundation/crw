#!/usr/bin/env python3
import json
import sys

LABELS = ["nsfl", "nsfw", "sfw"]
IMAGE_SIZE = 224


def main():
    if len(sys.argv) != 3:
        print("usage: classify.py <model.onnx> <image_path>", file=sys.stderr)
        return 1

    model_path, image_path = sys.argv[1], sys.argv[2]

    import numpy as np
    import onnxruntime
    from PIL import Image

    image = Image.open(image_path)
    image = image.convert("RGB").resize((IMAGE_SIZE, IMAGE_SIZE), Image.BILINEAR)
    array = np.asarray(image, dtype=np.float32)
    array = array.transpose(2, 0, 1)[np.newaxis, :, :, :]

    options = onnxruntime.SessionOptions()
    options.intra_op_num_threads = 1
    options.inter_op_num_threads = 1

    session = onnxruntime.InferenceSession(
        model_path, sess_options=options, providers=["CPUExecutionProvider"]
    )
    outputs = session.run(None, {"image": array})
    scores = outputs[0][0]

    result = {label: float(score) for label, score in zip(LABELS, scores)}
    print(json.dumps(result))
    return 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except Exception as exc:
        print(f"classify.py failed: {exc}", file=sys.stderr)
        sys.exit(1)
