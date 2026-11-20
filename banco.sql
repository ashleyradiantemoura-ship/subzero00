-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 19/11/2026 às 20:24
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `subzero`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `confrontos`
--

CREATE TABLE `confrontos` (
  `id` int(10) UNSIGNED NOT NULL,
  `time_casa` int(10) UNSIGNED NOT NULL,
  `time_visitante` int(10) UNSIGNED NOT NULL,
  `gols_casa` int(10) UNSIGNED DEFAULT NULL,
  `gols_visitante` int(10) UNSIGNED DEFAULT NULL,
  `decisao` enum('normal','prorrogacao','shootout') DEFAULT NULL,
  `confirmado` tinyint(1) NOT NULL DEFAULT 0,
  `data_hora` datetime DEFAULT NULL,
  `quadra` varchar(80) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `confrontos`
--

INSERT INTO `confrontos` (`id`, `time_casa`, `time_visitante`, `gols_casa`, `gols_visitante`, `decisao`, `confirmado`, `data_hora`, `quadra`, `cidade`, `estado`) VALUES
(2, 3, 4, 4, 3, 'prorrogacao', 1, NULL, NULL, NULL, NULL),
(4, 4, 1, 2, 5, 'normal', 0, NULL, NULL, NULL, NULL),
(5, 1, 3, 6, 10, 'normal', 0, NULL, NULL, NULL, NULL),
(6, 5, 3, 9, 10, 'normal', 0, NULL, NULL, NULL, NULL),
(7, 3, 1, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `favoritos`
--

CREATE TABLE `favoritos` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('time','jogador','confronto') NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `favoritos`
--

INSERT INTO `favoritos` (`id`, `usuario_id`, `tipo`, `item_id`, `criado_em`) VALUES
(1, 1, 'confronto', 6, '2026-11-19 19:13:16');

-- --------------------------------------------------------

--
-- Estrutura para tabela `jogadores`
--

CREATE TABLE `jogadores` (
  `id` int(10) UNSIGNED NOT NULL,
  `time_id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(80) NOT NULL,
  `numero` tinyint(3) UNSIGNED NOT NULL,
  `jogos_ajuste` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `pontos_ajuste` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `jogadores`
--

INSERT INTO `jogadores` (`id`, `time_id`, `nome`, `numero`, `jogos_ajuste`, `pontos_ajuste`) VALUES
(1, 1, 'Brad Marchand', 63, 0, 0),
(2, 1, 'David Pastrnak', 88, 0, 0),
(3, 1, 'Charlie McAvoy', 73, 0, 0),
(4, 1, 'Pavel Zacha', 18, 0, 0),
(5, 1, 'Jeremy Swayman', 1, 0, 0),
(6, 3, 'Artemi Panarin', 10, 0, 0),
(7, 3, 'Mika Zibanejad', 93, 0, 0),
(8, 3, 'Adam Fox', 23, 0, 0),
(9, 3, 'Chris Kreider', 20, 0, 0),
(10, 3, 'Igor Shesterkin', 31, 0, 0),
(11, 4, 'Connor Bedard', 98, 0, 0),
(12, 4, 'Nick Foligno', 17, 0, 0),
(13, 4, 'Seth Jones', 4, 0, 0),
(14, 4, 'Taylor Hall', 71, 0, 0),
(15, 4, 'Petr Mrazek', 34, 0, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `sumulas`
--

CREATE TABLE `sumulas` (
  `id` int(10) UNSIGNED NOT NULL,
  `confronto_id` int(10) UNSIGNED NOT NULL,
  `jogador_id` int(10) UNSIGNED NOT NULL,
  `gols` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `assistencias` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `times`
--

CREATE TABLE `times` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `foto_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `times`
--

INSERT INTO `times` (`id`, `nome`, `foto_url`) VALUES
(1, 'Boston Bruins', NULL),
(2, 'Toronto Maple Leafs', NULL),
(3, 'New York Rangers', NULL),
(4, 'Chicago Blackhawks', NULL),
(5, 'rosca doce', NULL),
(6, 'ducks', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `perfil` enum('admin','visualizador') NOT NULL DEFAULT 'visualizador'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `perfil`) VALUES
(1, 'Administrador', 'admin@subzero.com', '$2y$12$rpN7Mt8HmStR19k7mUGuA.w06XqlaqSNCGzHk.sNza0ncyxc692rq', 'admin'),
(2, 'Visualizador', 'viewer@subzero.com', '$2y$12$QDjXw3.oe9sThJyrpK4WUOVxBGKs5Y6p4eT7hNUGXLCbHwrMZ2G5K', 'visualizador');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `confrontos`
--
ALTER TABLE `confrontos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `time_casa` (`time_casa`),
  ADD KEY `time_visitante` (`time_visitante`);

--
-- Índices de tabela `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_favorito` (`usuario_id`,`tipo`,`item_id`);

--
-- Índices de tabela `jogadores`
--
ALTER TABLE `jogadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_time_numero` (`time_id`,`numero`);

--
-- Índices de tabela `sumulas`
--
ALTER TABLE `sumulas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_confronto_jogador` (`confronto_id`,`jogador_id`),
  ADD KEY `jogador_id` (`jogador_id`);

--
-- Índices de tabela `times`
--
ALTER TABLE `times`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `confrontos`
--
ALTER TABLE `confrontos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `jogadores`
--
ALTER TABLE `jogadores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `sumulas`
--
ALTER TABLE `sumulas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `times`
--
ALTER TABLE `times`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `confrontos`
--
ALTER TABLE `confrontos`
  ADD CONSTRAINT `confrontos_ibfk_1` FOREIGN KEY (`time_casa`) REFERENCES `times` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `confrontos_ibfk_2` FOREIGN KEY (`time_visitante`) REFERENCES `times` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `fk_favoritos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `jogadores`
--
ALTER TABLE `jogadores`
  ADD CONSTRAINT `jogadores_ibfk_1` FOREIGN KEY (`time_id`) REFERENCES `times` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `sumulas`
--
ALTER TABLE `sumulas`
  ADD CONSTRAINT `sumulas_ibfk_1` FOREIGN KEY (`confronto_id`) REFERENCES `confrontos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sumulas_ibfk_2` FOREIGN KEY (`jogador_id`) REFERENCES `jogadores` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
