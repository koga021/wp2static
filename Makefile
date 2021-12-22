

help:
	@echo "Help information"

rebuild-config: remove-config-docs configure-docs configure-install

remove-config-docs:
	@cd docs/scripts && ./remove_docs_config.sh

configure-docs:
	@cd docs/scripts && ./init_docs_config.sh

configure-install:
	@cd docs/scripts && ./install_docs.sh


run-docs:
	@echo "Running MkDocs on Local Simple Server"
	@cd docs/wp2static-docs-projetc && mkdocs serve


build-docs:
	@echo "Build Docs Files"
	cd docs/wp2static-docs-projetc && mkdocs build