# Alur Verifikasi Surat Tugas / Perjalanan Dinas

---

## Anggota DPRD

```text
Role            : anggota_dprd
Tipe OPD   : dprd
```

| Kategori         | Alur Verifikasi                              |
| :--------------- | :------------------------------------------- |
| **Dalam Daerah** | Sekretaris Dewan $\rightarrow$ Pimpinan DPRD |
| **LDDP**         | Sekretaris Dewan $\rightarrow$ Pimpinan DPRD |
| **LDLP**         | Sekretaris Dewan $\rightarrow$ Pimpinan DPRD |

---

## # Staff DPRD

```text
Role            : selain_anggota_dprd
Tipe OPD   : dprd
```

| Kategori         | Alur Verifikasi                      |
| :--------------- | :----------------------------------- |
| **Dalam Daerah** | Kabag $\rightarrow$ Sekretaris Dewan |
| **LDDP**         | Kabag $\rightarrow$ Sekretaris Dewan |
| **LDLP**         | Kabag $\rightarrow$ Sekretaris Dewan |

---

## # Sekwan

```text
Role            : sekwan
Tipe OPD   : dprd
```

| Kategori         | Alur Verifikasi                                             |
| :--------------- | :---------------------------------------------------------- |
| **Dalam Daerah** | Sekretaris Dewan $\rightarrow$ Sekda                        |
| **LDDP**         | Sekretaris Dewan $\rightarrow$ Sekda $\rightarrow$ Walikota |
| **LDLP**         | Sekretaris Dewan $\rightarrow$ Sekda $\rightarrow$ Walikota |

---

## # Kepala OPD

```text
Role            : kepala_opd
Tipe OPD   : opd
```

| Kategori         | Alur Verifikasi                                                                    |
| :--------------- | :--------------------------------------------------------------------------------- |
| **Dalam Daerah** | Sekretaris OPD $\rightarrow$ Kepala OPD                                            |
| **LDDP**         | Sekretaris OPD $\rightarrow$ Kepala OPD $\rightarrow$ Sekda $\rightarrow$ Walikota |
| **LDLP**         | Sekretaris OPD $\rightarrow$ Kepala OPD $\rightarrow$ Sekda $\rightarrow$ Walikota |
