
import hashlib

with open("test_dict", "w") as f:
    for i in range(0, 2500000):
        f.write(str(i).zfill(8)*100)
        f.write("=")
        f.write(hashlib.md5(str(i).encode('ascii')).hexdigest()*100)
        f.write("\n")
