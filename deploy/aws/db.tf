resource "aws_db_subnet_group" "default" {
  name       = "rds-private-subnet-group"
  subnet_ids = aws_subnet.rds.*.id

  tags = {
	Name = "My DB subnet group"
  }
}

resource "aws_db_instance" "default" {
  port                 = var.db_port
  allocated_storage    = 20
  storage_type         = "gp2"
  engine               = "postgres"
  engine_version       = "11.6"
  instance_class       = "db.t2.micro"
  name                 = "mydb"
  username             = var.db_username
  password             = var.db_password
  parameter_group_name = "default.postgres11"
  db_subnet_group_name = aws_db_subnet_group.default.id
  vpc_security_group_ids = [aws_security_group.ecs_rds.id]
  final_snapshot_identifier = "lti-shim-db-backup-${formatdate("YYYYMMDDhhmm", timestamp())}"
}
