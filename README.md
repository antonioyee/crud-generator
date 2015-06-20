# [CRUD Generator](http://crud.mx/)
CRUD's Generator from a SQL script (with one table declared).

### Script SQL

Example of a processing script generator, `.sql` should have declared a table only if CRUD generate multiple file, run separate script by script:

```
DROP TABLE IF EXISTS `personas`;

CREATE TABLE `personas` (
  `id_persona` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) DEFAULT NULL,
  `apellido_paterno` varchar(50) DEFAULT NULL COMMENT '   ',
  `apellido_materno` varchar(50) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `edad` int(11) DEFAULT NULL,
  `telefono` varchar(45) DEFAULT NULL,
  `celular` varchar(45) DEFAULT NULL,
  `correo_electronico` varchar(100) DEFAULT NULL,
  `calle` varchar(255) DEFAULT NULL,
  `numero_exterior` varchar(5) DEFAULT NULL,
  `numero_interior` varchar(5) DEFAULT NULL,
  `colonia` varchar(45) DEFAULT NULL,
  `codigo_postal` varchar(5) DEFAULT NULL,
  `municipio` varchar(45) DEFAULT NULL,
  `estado` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_persona`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### Config

Create folders `/components` and `/tables` at the root of the project, with permissions to perform the upload.

### Developer

**Antonio Yee**

- [yee.antonio@gmail.com](mailto:yee.antonio@gmail.com)
- <http://antonioyee.mx>
- <https://twitter.com/antonioyee>